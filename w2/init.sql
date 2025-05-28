-- Create users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(128) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create purchases table to track purchase history
CREATE TABLE purchases (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    purchase_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create shopping_lists table
CREATE TABLE shopping_lists (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create shopping_list_items table
CREATE TABLE shopping_list_items (
    id SERIAL PRIMARY KEY,
    shopping_list_id INTEGER REFERENCES shopping_lists(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    is_purchased BOOLEAN DEFAULT FALSE
);

-- Insert sample data
INSERT INTO users (username, password_hash) VALUES 
('demo', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f'); -- password: "demo"

-- Insert sample products for demo user
INSERT INTO products (user_id, name) VALUES 
(1, 'Mjölk'),
(1, 'Bröd'),
(1, 'Ägg'),
(1, 'Smör'),
(1, 'Kött'),
(1, 'Potatis'),
(1, 'Tomat'),
(1, 'Lök');

-- Insert sample purchase history
INSERT INTO purchases (user_id, product_id, purchase_date) VALUES 
(1, 1, '2024-01-01'), -- Mjölk
(1, 1, '2024-01-05'),
(1, 1, '2024-01-08'),
(1, 1, '2024-01-13'),
(1, 2, '2024-01-03'), -- Bröd
(1, 2, '2024-01-07'),
(1, 2, '2024-01-14'),
(1, 3, '2024-01-02'), -- Ägg
(1, 3, '2024-01-09'),
(1, 3, '2024-01-16'),
(1, 4, '2024-01-01'), -- Smör
(1, 4, '2024-01-15');