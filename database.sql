CREATE TABLE `call_signs`
(
    `call_sign_id` INT         NOT NULL AUTO_INCREMENT,
    `value`        VARCHAR(20) NOT NULL,
    `region_id`    INT         NOT NULL,
    PRIMARY KEY (`call_sign_id`),
    UNIQUE `call_sign_value` (`value`(20))
) ENGINE = InnoDB;

CREATE TABLE `regions`
(
    `region_id` INT         NOT NULL AUTO_INCREMENT,
    `title`     VARCHAR(100) NOT NULL,
    PRIMARY KEY (`region_id`),
    UNIQUE `title` (`title`(100))
) ENGINE = InnoDB;

CREATE TABLE `status`
(
    `status_id`       INT          NOT NULL AUTO_INCREMENT,
    `call_sign_id`    INT          NOT NULL,
    `date`            DATETIME     NOT NULL,
    `from`            VARCHAR(20)  NOT NULL,
    `via`             VARCHAR(20)  NOT NULL,
    `path`            VARCHAR(255) NOT NULL,
    `symbol`          VARCHAR(5) NULL DEFAULT NULL,
    `symbol_table`    VARCHAR(5) NULL DEFAULT NULL,
    `latitude`        FLOAT         NULL DEFAULT NULL,
    `longitude`       FLOAT NULL DEFAULT NULL,
    PRIMARY KEY (`status_id`),
    UNIQUE `call_sing` (`call_sign_id`)
) ENGINE = InnoDB;