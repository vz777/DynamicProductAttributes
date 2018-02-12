
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- dynamic_product_attribute
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `dynamic_product_attribute`;

CREATE TABLE `dynamic_product_attribute`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `cart_item_id` INTEGER NOT NULL,
    `attribute_id` INTEGER NOT NULL,
    `attribute_value` LONGTEXT,
    PRIMARY KEY (`id`),
    INDEX `FI_dynamic_product_attribute_cart_item` (`cart_item_id`),
    INDEX `FI_dynamic_product_attribute_attribute` (`attribute_id`),
    CONSTRAINT `fk_dynamic_product_attribute_cart_item`
        FOREIGN KEY (`cart_item_id`)
        REFERENCES `cart_item` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_dynamic_product_attribute_attribute`
        FOREIGN KEY (`attribute_id`)
        REFERENCES `attribute` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
