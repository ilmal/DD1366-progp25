CREATE TABLE Users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE Products (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    UNIQUE (user_id, name)
);

CREATE TABLE ShoppingLists (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(id) ON DELETE CASCADE,
    created_at DATE NOT NULL,
    confirmed_at DATE
);

CREATE TABLE ShoppingListItems (
    id SERIAL PRIMARY KEY,
    shopping_list_id INT REFERENCES ShoppingLists(id) ON DELETE CASCADE,
    product_id INT REFERENCES Products(id) ON DELETE CASCADE,
    purchased BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE Purchases (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES Users(id) ON DELETE CASCADE,
    product_id INT REFERENCES Products(id) ON DELETE CASCADE,
    purchase_date DATE NOT NULL
);
