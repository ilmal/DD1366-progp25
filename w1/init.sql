CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE items (
    item_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    item_name VARCHAR(100) NOT NULL,
    discontinued BOOLEAN DEFAULT FALSE,
    purchased BOOLEAN DEFAULT FALSE,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purchases (
    purchase_id SERIAL PRIMARY KEY,
    item_id INT REFERENCES items(item_id) ON DELETE CASCADE,
    purchase_date DATE NOT NULL
);

CREATE TABLE replacements (
    replacement_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    original_item_id INT REFERENCES items(item_id) ON DELETE CASCADE,
    replacement_item_id INT REFERENCES items(item_id) ON DELETE CASCADE,
    replaced_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);