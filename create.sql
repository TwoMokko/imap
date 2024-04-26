CREATE TABLE IF NOT EXISTS <TABLE> (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
`visitor_id` INT,
`date` TIMESTAMP,
`subject` VARCHAR(250),
`recipient` VARCHAR(50),
`sender` VARCHAR(50),
`message`	 TEXT,
`scenario` SET('прямое', 'подмена адреса', 'откуда-то еще'),
FOREIGN KEY (visitor_id) REFERENCES $foreignTable($foreignField)
)