<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function getDb() {
    static $db = null;
    if ($db === null) {
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $dbname = getenv('DB_NAME');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        try {
            $db = new PDO($dsn, $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    return $db;
}

function selectPwd($username) {
    $db = getDb();
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['password_hash'] : null;
}

function getUserId($username) {
    $db = getDb();
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    return $stmt->fetchColumn();
}

/**
 * Calculate the average interval between purchases for an item
 * Returns an array with average interval and dates information
 */
function calculatePurchaseInterval($item_id) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT purchase_date 
        FROM purchases 
        WHERE item_id = :item_id 
        ORDER BY purchase_date ASC
    ");
    $stmt->execute(['item_id' => $item_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $total_intervals = 0;
    $count = 0;
    $intervals_detail = [];
    
    // If we have at least two purchases, calculate interval
    if (count($dates) >= 2) {
        for ($i = 1; $i < count($dates); $i++) {
            $prev_date = new DateTime($dates[$i-1]);
            $curr_date = new DateTime($dates[$i]);
            $interval_days = $prev_date->diff($curr_date)->days;
            
            $intervals_detail[] = $interval_days;
            $total_intervals += $interval_days;
            $count++;
        }
        
        $avg_interval = $count > 0 ? $total_intervals / $count : null;
        
        return [
            'avg_interval' => $avg_interval,
            'intervals' => $intervals_detail,
            'purchase_dates' => $dates,
            'purchase_count' => count($dates)
        ];
    }
    
    return [
        'avg_interval' => null,
        'intervals' => [],
        'purchase_dates' => $dates,
        'purchase_count' => count($dates)
    ];
}

function getSuggestedItems($user_id, $list_id) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT i.item_id, i.item_name,
               MAX(p.purchase_date) AS last_purchase
        FROM items i
        LEFT JOIN purchases p ON i.item_id = p.item_id
        WHERE i.user_id = :user_id AND i.item_id NOT IN (
            SELECT item_id FROM shopping_list_items WHERE list_id = :list_id
        )
        GROUP BY i.item_id, i.item_name
        ORDER BY i.item_name
    ");
    $stmt->execute(['user_id' => $user_id, 'list_id' => $list_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $suggested = [];
    $current_date = new DateTime();

    foreach ($items as $item) {
        // Calculate purchase interval for this item
        $interval_data = calculatePurchaseInterval($item['item_id']);
        $avg_interval = $interval_data['avg_interval'];
        
        // If never purchased before, suggest it
        if ($item['last_purchase'] === null) {
            $item['reason'] = 'Aldrig köpt tidigare';
            $item['avg_interval'] = null;
            $item['interval_detail'] = '';
            $suggested[] = $item;
        } 
        // If we have calculated an average interval
        elseif ($avg_interval !== null) {
            $last_purchase = new DateTime($item['last_purchase']);
            $days_since_last = $current_date->diff($last_purchase)->days;
            
            // Format the interval details for display
            $interval_str = implode(', ', $interval_data['intervals']);
            $dates_str = implode(', ', array_map(function($date) {
                return date('Y-m-d', strtotime($date));
            }, $interval_data['purchase_dates']));
            
            // If time since last purchase exceeds the average interval, suggest it
            if ($days_since_last >= $avg_interval) {
                $item['days_since_last'] = $days_since_last;
                $item['avg_interval'] = round($avg_interval, 1);
                $item['reason'] = 'Beräknat förbrukningsintervall (' . round($avg_interval, 1) . ' dagar) har passerats';
                $item['interval_detail'] = "Inköpsdatum: $dates_str\nBeräknade intervall (dagar): $interval_str";
                $suggested[] = $item;
            }
        }
        // For items with only one purchase (can't calculate average interval)
        elseif (count($interval_data['purchase_dates']) == 1) {
            $last_purchase = new DateTime($item['last_purchase']);
            $days_since_last = $current_date->diff($last_purchase)->days;
            
            // If it's been at least 30 days since last purchase, suggest it
            if ($days_since_last >= 30) {
                $item['days_since_last'] = $days_since_last;
                $item['avg_interval'] = null;
                $item['reason'] = 'Köpt endast en gång för ' . $days_since_last . ' dagar sedan';
                $item['interval_detail'] = "Inköpsdatum: " . date('Y-m-d', strtotime($item['last_purchase']));
                $suggested[] = $item;
            }
        }
    }
    
    return $suggested;
}

function getShoppingListItems($list_id) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT sli.list_item_id, i.item_id, i.item_name
        FROM shopping_list_items sli
        JOIN items i ON sli.item_id = i.item_id
        WHERE sli.list_id = :list_id AND sli.purchased = FALSE
    ");
    $stmt->execute(['list_id' => $list_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAvailableItems($user_id, $list_id) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT i.item_id, i.item_name
        FROM items i
        WHERE i.user_id = :user_id AND i.item_id NOT IN (
            SELECT item_id FROM shopping_list_items WHERE list_id = :list_id
        )
        ORDER BY i.item_name
    ");
    $stmt->execute(['user_id' => $user_id, 'list_id' => $list_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getShoppingListById($user_id, $list_id) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT list_id, name, created_date
        FROM shopping_lists
        WHERE list_id = :list_id AND user_id = :user_id
    ");
    $stmt->execute(['list_id' => $list_id, 'user_id' => $user_id]);
    $list = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($list) {
        $stmt_items = $db->prepare("
            SELECT sli.list_item_id, i.item_id, i.item_name
            FROM shopping_list_items sli
            JOIN items i ON sli.item_id = i.item_id
            WHERE sli.list_id = :list_id
        ");
        $stmt_items->execute(['list_id' => $list_id]);
        $list['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }
    return $list;
}

function getAllShoppingLists($user_id) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT list_id, name, created_date
        FROM shopping_lists
        WHERE user_id = :user_id
        ORDER BY created_date DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lists as &$list) {
        $list['display_name'] = $list['name'] ?: "Inköpslista skapad " . date('Y-m-d H:i', strtotime($list['created_date']));
    }
    return $lists;
}

function createShoppingList($user_id, $name = null) {
    $db = getDb();
    $stmt = $db->prepare("
        INSERT INTO shopping_lists (user_id, name)
        VALUES (:user_id, :name)
        RETURNING list_id
    ");
    $stmt->execute(['user_id' => $user_id, 'name' => $name ?: null]);
    return $stmt->fetchColumn();
}

function addItemToDatabase($user_id, $item_name) {
    $db = getDb();
    $stmt = $db->prepare("
        INSERT INTO items (user_id, item_name)
        VALUES (:user_id, :item_name)
    ");
    return $stmt->execute([
        'user_id' => $user_id,
        'item_name' => $item_name
    ]);
}

function removeItemFromDatabase($user_id, $item_id) {
    $db = getDb();
    $stmt = $db->prepare("
        DELETE FROM items
        WHERE item_id = :item_id AND user_id = :user_id
    ");
    return $stmt->execute(['item_id' => $item_id, 'user_id' => $user_id]);
}

function getAllItemsForUser($user_id) {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT i.item_id, i.item_name,
               MAX(p.purchase_date) AS last_purchase,
               COUNT(p.purchase_id) AS purchase_count
        FROM items i
        LEFT JOIN purchases p ON i.item_id = p.item_id
        WHERE i.user_id = :user_id
        GROUP BY i.item_id, i.item_name
        ORDER BY i.item_name
    ");
    $stmt->execute(['user_id' => $user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate average intervals for all items
    foreach ($items as &$item) {
        $interval_data = calculatePurchaseInterval($item['item_id']);
        $item['avg_interval'] = $interval_data['avg_interval'] ? round($interval_data['avg_interval'], 1) : null;
        $item['purchase_dates'] = $interval_data['purchase_dates'];
        $item['intervals'] = $interval_data['intervals'];
    }
    
    return $items;
}

function recordPurchase($item_id) {
    $db = getDb();
    $stmt = $db->prepare("
        INSERT INTO purchases (item_id, purchase_date)
        VALUES (:item_id, CURRENT_DATE)
    ");
    return $stmt->execute(['item_id' => $item_id]);
}

function markItemAsPurchased($user_id, $list_item_id) {
    $db = getDb();
    $stmt = $db->prepare("
        UPDATE shopping_list_items sli
        SET purchased = TRUE
        FROM shopping_lists sl
        WHERE sli.list_id = sl.list_id AND sl.user_id = :user_id AND sli.list_item_id = :list_item_id
    ");
    return $stmt->execute(['user_id' => $user_id, 'list_item_id' => $list_item_id]);
}
?>