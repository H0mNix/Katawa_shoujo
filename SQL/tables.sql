-- Table des utilisateurs
CREATE TABLE users
(
    userid      SERIAL CONSTRAINT users_pk PRIMARY KEY, 
    identifiant VARCHAR(50)  NOT NULL,
    motdepasse  VARCHAR(255) NOT NULL,
    data        TEXT
);

-- Table des sauvegardes des utilisateurs
CREATE TABLE saves
(
    save_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(userid),
    save_number INTEGER NOT NULL CHECK (save_number >= 1 AND save_number <= 5),
    save_name VARCHAR DEFAULT '',
    save_date BIGINT NOT NULL,
    save_data TEXT NOT NULL,
    UNIQUE(user_id, save_number)
);
