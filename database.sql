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
    `region_id` INT          NOT NULL AUTO_INCREMENT,
    `title`     VARCHAR(100) NOT NULL,
    PRIMARY KEY (`region_id`),
    UNIQUE `title` (`title`(100))
) ENGINE = InnoDB;

CREATE TABLE `status`
(
    `call_sign_id`          INT          NOT NULL,
    `date`                  DATETIME     NOT NULL,
    `beacon_date`           DATETIME     NULL DEFAULT NULL,
    `beacon_from`           VARCHAR(20)  NULL DEFAULT NULL,
    `beacon_path`           VARCHAR(255) NULL DEFAULT NULL,
    `beacon_symbol`         VARCHAR(5)   NULL DEFAULT NULL,
    `beacon_symbol_table`   VARCHAR(5)   NULL DEFAULT NULL,
    `beacon_latitude`       FLOAT        NULL DEFAULT NULL,
    `beacon_longitude`      FLOAT        NULL DEFAULT NULL,
    `activity_date`         DATETIME     NULL DEFAULT NULL,
    `activity_from`         VARCHAR(20)  NULL DEFAULT NULL,
    `activity_path`         VARCHAR(255) NULL DEFAULT NULL,
    `activity_symbol`       VARCHAR(5)   NULL DEFAULT NULL,
    `activity_symbol_table` VARCHAR(5)   NULL DEFAULT NULL,
    `activity_latitude`     FLOAT        NULL DEFAULT NULL,
    `activity_longitude`    FLOAT        NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign_id`)
) ENGINE = InnoDB;

CREATE TABLE `proposals`
(
    `call_sign` VARCHAR(20)  NOT NULL,
    `date`      DATETIME     NOT NULL,
    `from`      VARCHAR(20)  NOT NULL,
    `path`      VARCHAR(255) NOT NULL,
    `comment`   TEXT         NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign`)
) ENGINE = InnoDB;