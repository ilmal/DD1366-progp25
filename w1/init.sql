CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE items (
    item_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    item_name VARCHAR(100) NOT NULL
);

CREATE TABLE purchases (
    purchase_id SERIAL PRIMARY KEY,
    item_id INT REFERENCES items(item_id) ON DELETE CASCADE,
    purchase_date DATE NOT NULL
);

CREATE TABLE shopping_lists (
    list_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    name VARCHAR(100),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE shopping_list_items (
    list_item_id SERIAL PRIMARY KEY,
    list_id INT REFERENCES shopping_lists(list_id) ON DELETE CASCADE,
    item_id INT REFERENCES items(item_id) ON DELETE CASCADE,
    purchased BOOLEAN DEFAULT FALSE,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_list_item UNIQUE (list_id, item_id)
);