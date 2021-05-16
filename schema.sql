DROP TABLE IF EXISTS user_prediction;
CREATE TABLE user_prediction(
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT,
    game_id INT,
    first_player_score INT DEFAULT 0,
    second_player_score INT DEFAULT 0,
    PRIMARY KEY(id)    
)DEFAULT CHARACTER SET cp1251 COLLATE cp1251_bin;

DROP TABLE IF EXISTS game;
CREATE TABLE game(
    id INT AUTO_INCREMENT NOT NULL,
    tournament_id INT,
    level_id INT,
    first_player_name VARCHAR(250),
    second_player_name VARCHAR(250),    
    first_player_score INT DEFAULT 0,
    second_player_score INT DEFAULT 0,
    child_id INT,
    PRIMARY KEY(id)    
)DEFAULT CHARACTER SET cp1251 COLLATE cp1251_bin;

DROP TABLE IF EXISTS tournament;
CREATE TABLE tournament(
    id INT AUTO_INCREMENT NOT NULL,
    name varchar(250),
    date_start INT,    
    pairs_amount INT,    
    PRIMARY KEY(id)    
)DEFAULT CHARACTER SET cp1251 COLLATE cp1251_bin;


DROP TABLE IF EXISTS levels;
CREATE TABLE levels(
    id INT AUTO_INCREMENT NOT NULL,  
    number INT,
    tournament_id INT,
    date_start INT,
    max_goals INT,
    PRIMARY KEY(id)    
)DEFAULT CHARACTER SET cp1251 COLLATE cp1251_bin;

DROP TABLE IF EXISTS castom_question;
CREATE TABLE castom_question(
    id INT AUTO_INCREMENT NOT NULL,  
    tounament_id INT,
    text VARCHAR(250),
    right_answer INT,
    PRIMARY KEY(id)    
)DEFAULT CHARACTER SET cp1251 COLLATE cp1251_bin;

DROP TABLE IF EXISTS user_castom_question;
CREATE TABLE user_castom_question(
    id INT AUTO_INCREMENT NOT NULL,  
    user_id INT,
    castom_req_id INT,    
    answer INT,
    PRIMARY KEY(id)    
)DEFAULT CHARACTER SET cp1251 COLLATE cp1251_bin;

ALTER TABLE `user_castom_question` ADD UNIQUE `req_user` (`castom_req_id`, `user_id`);
ALTER TABLE `game` ADD `date_start` INT(4) NULL AFTER `child_id`;
ALTER TABLE `castom_question` CHANGE `right_answer` `right_answer` VARCHAR(50) NULL DEFAULT NULL;
ALTER TABLE `user_castom_question` CHANGE `answer` `answer` VARCHAR(250) NULL DEFAULT NULL;
