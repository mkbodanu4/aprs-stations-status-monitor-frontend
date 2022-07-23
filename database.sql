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

CREATE TABLE `proposals`
(
    `call_sign` VARCHAR(20)  NOT NULL,
    `date`      DATETIME     NOT NULL,
    `from`      VARCHAR(20)  NOT NULL,
    `path`      VARCHAR(255) NOT NULL,
    `comment`   TEXT         NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign`)
) ENGINE = InnoDB;

CREATE TABLE `positions`
(
    `call_sign_id` INT         NOT NULL,
    `date`         DATETIME    NOT NULL,
    `from`         VARCHAR(20) NOT NULL,
    `path`         TEXT        NULL,
    `symbol_table` VARCHAR(5)  NULL,
    `symbol`       VARCHAR(5)  NULL,
    `latitude`     FLOAT       NULL,
    `longitude`    FLOAT       NULL,
    `comment`      TEXT        NULL DEFAULT NULL,
    `raw`          TEXT        NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign_id`)
) ENGINE = InnoDB;

CREATE TABLE `telemetry`
(
    `call_sign_id` INT         NOT NULL,
    `date`         DATETIME    NOT NULL,
    `from`         VARCHAR(20) NOT NULL,
    `path`         TEXT        NULL,
    `comment`      TEXT        NULL DEFAULT NULL,
    `raw`          TEXT        NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign_id`)
) ENGINE = InnoDB;

CREATE TABLE `weather`
(
    `call_sign_id` INT         NOT NULL,
    `date`         DATETIME    NOT NULL,
    `from`         VARCHAR(20) NOT NULL,
    `path`         TEXT        NULL,
    `symbol_table` VARCHAR(5)  NULL,
    `symbol`       VARCHAR(5)  NULL,
    `latitude`     FLOAT       NULL,
    `longitude`    FLOAT       NULL,
    `comment`      TEXT        NULL DEFAULT NULL,
    `raw`          TEXT        NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign_id`)
) ENGINE = InnoDB;

CREATE TABLE `objects`
(
    `call_sign_id` INT          NOT NULL,
    `date`         DATETIME     NOT NULL,
    `from`         VARCHAR(20)  NOT NULL,
    `object`       VARCHAR(100) NOT NULL,
    `path`         TEXT         NULL,
    `symbol_table` VARCHAR(5)   NULL,
    `symbol`       VARCHAR(5)   NULL,
    `latitude`     FLOAT        NULL,
    `longitude`    FLOAT        NULL,
    `comment`      TEXT         NULL DEFAULT NULL,
    `raw`          TEXT         NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign_id`)
) ENGINE = InnoDB;

CREATE TABLE `routing`
(
    `call_sign_id` INT         NOT NULL,
    `date`         DATETIME    NOT NULL,
    `from`         VARCHAR(20) NOT NULL,
    `path`         TEXT        NULL,
    `symbol_table` VARCHAR(5)  NULL,
    `symbol`       VARCHAR(5)  NULL,
    `latitude`     FLOAT       NULL,
    `longitude`    FLOAT       NULL,
    `comment`      TEXT        NULL DEFAULT NULL,
    `raw`          TEXT        NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign_id`)
) ENGINE = InnoDB;

CREATE TABLE `status`
(
    `call_sign_id` INT         NOT NULL,
    `date`         DATETIME    NOT NULL,
    `from`         VARCHAR(20) NOT NULL,
    `path`         TEXT        NULL,
    `symbol_table` VARCHAR(5)  NULL,
    `symbol`       VARCHAR(5)  NULL,
    `latitude`     FLOAT       NULL,
    `longitude`    FLOAT       NULL,
    `comment`      TEXT        NULL DEFAULT NULL,
    `raw`          TEXT        NULL DEFAULT NULL,
    PRIMARY KEY (`call_sign_id`)
) ENGINE = InnoDB;