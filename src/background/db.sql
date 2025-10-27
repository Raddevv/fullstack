SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema forevertools
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `forevertools` DEFAULT CHARACTER SET utf8 ;
USE `forevertools` ;

-- -----------------------------------------------------
-- Table `forevertools`.`klant`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`klant` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `voornaam` VARCHAR(50) NOT NULL,
  `achternaam` VARCHAR(50) NOT NULL,
  `wachtwoord` VARCHAR(255) NOT NULL,
  `admin` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`product` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(200) NOT NULL,
  `fabriekherkomst` VARCHAR(200) NOT NULL,
  `prijs` DECIMAL NOT NULL,
  `waardeinkoop` DECIMAL NOT NULL,
  `waardeverkoop` DECIMAL NOT NULL,
  `bestelling_id` INT NOT NULL,
  PRIMARY KEY (`id`, `bestelling_id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`locaties`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`locaties` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `adres` VARCHAR(100) NOT NULL,
  `postcode` VARCHAR(7) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`medewerker`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`medewerker` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `admin` TINYINT NOT NULL,
  `voornaam` VARCHAR(50) NOT NULL,
  `achternaam` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`bestelling`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`bestelling` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `totale_kosten` DECIMAL NOT NULL,
  `klant_id` INT NOT NULL,
  `medewerker_id` INT NOT NULL,
  PRIMARY KEY (`id`, `klant_id`, `medewerker_id`),
  INDEX `fk_bestelling_medewerker1_idx` (`medewerker_id` ASC) VISIBLE,
  INDEX `fk_bestelling_klant1_idx` (`klant_id` ASC) VISIBLE,
  CONSTRAINT `fk_bestelling_medewerker1`
    FOREIGN KEY (`medewerker_id`)
    REFERENCES `forevertools`.`medewerker` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_bestelling_klant1`
    FOREIGN KEY (`klant_id`)
    REFERENCES `forevertools`.`klant` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`voorraad`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`voorraad` (
  `id` INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`product_has_locaties`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`product_has_locaties` (
  `product_id` INT NOT NULL,
  `product_bestelling_id` INT NOT NULL,
  `locaties_id` INT NOT NULL,
  `locaties_voorraad_id` INT NOT NULL,
  `aantal` VARCHAR(10000) NOT NULL,
  PRIMARY KEY (`product_id`, `product_bestelling_id`, `locaties_id`, `locaties_voorraad_id`),
  INDEX `fk_product_has_locaties_locaties1_idx` (`locaties_id` ASC) VISIBLE,
  INDEX `fk_product_has_locaties_voorraad1_idx` (`locaties_voorraad_id` ASC) VISIBLE,
  INDEX `fk_product_has_locaties_product1_idx` (`product_id` ASC, `product_bestelling_id` ASC) VISIBLE,
  CONSTRAINT `fk_product_has_locaties_product1`
    FOREIGN KEY (`product_id`, `product_bestelling_id`)
    REFERENCES `forevertools`.`product` (`id`, `bestelling_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_has_locaties_locaties1`
    FOREIGN KEY (`locaties_id`)
    REFERENCES `forevertools`.`locaties` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_has_locaties_voorraad1`
    FOREIGN KEY (`locaties_voorraad_id`)
    REFERENCES `forevertools`.`voorraad` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`locaties_has_medewerker`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`locaties_has_medewerker` (
  `locaties_id` INT NOT NULL,
  `medewerker_id` INT NOT NULL,
  PRIMARY KEY (`locaties_id`, `medewerker_id`),
  INDEX `fk_locaties_has_medewerker_medewerker1_idx` (`medewerker_id` ASC) VISIBLE,
  INDEX `fk_locaties_has_medewerker_locaties1_idx` (`locaties_id` ASC) VISIBLE,
  CONSTRAINT `fk_locaties_has_medewerker_locaties1`
    FOREIGN KEY (`locaties_id`)
    REFERENCES `forevertools`.`locaties` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_locaties_has_medewerker_medewerker1`
    FOREIGN KEY (`medewerker_id`)
    REFERENCES `forevertools`.`medewerker` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`factuur`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`factuur` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `prijs` DECIMAL NOT NULL,
  `klantvoornaam` VARCHAR(50) NOT NULL,
  `klantachternaam` VARCHAR(50) NOT NULL,
  `bedrijfnaam` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`bestelling_has_factuur`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`bestelling_has_factuur` (
  `bestelling_id` INT NOT NULL,
  `bestelling_klant_id` INT NOT NULL,
  `bestelling_medewerker_id` INT NOT NULL,
  `factuur_id` INT NOT NULL,
  PRIMARY KEY (`bestelling_id`, `bestelling_klant_id`, `bestelling_medewerker_id`, `factuur_id`),
  INDEX `fk_bestelling_has_factuur_factuur1_idx` (`factuur_id` ASC) VISIBLE,
  INDEX `fk_bestelling_has_factuur_bestelling1_idx` (`bestelling_id` ASC, `bestelling_klant_id` ASC, `bestelling_medewerker_id` ASC) VISIBLE,
  CONSTRAINT `fk_bestelling_has_factuur_bestelling1`
    FOREIGN KEY (`bestelling_id`, `bestelling_klant_id`, `bestelling_medewerker_id`)
    REFERENCES `forevertools`.`bestelling` (`id`, `klant_id`, `medewerker_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_bestelling_has_factuur_factuur1`
    FOREIGN KEY (`factuur_id`)
    REFERENCES `forevertools`.`factuur` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`factuur_has_medewerker`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`factuur_has_medewerker` (
  `factuur_id` INT NOT NULL,
  `medewerker_id` INT NOT NULL,
  PRIMARY KEY (`factuur_id`, `medewerker_id`),
  INDEX `fk_factuur_has_medewerker_medewerker1_idx` (`medewerker_id` ASC) VISIBLE,
  INDEX `fk_factuur_has_medewerker_factuur1_idx` (`factuur_id` ASC) VISIBLE,
  CONSTRAINT `fk_factuur_has_medewerker_factuur1`
    FOREIGN KEY (`factuur_id`)
    REFERENCES `forevertools`.`factuur` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_factuur_has_medewerker_medewerker1`
    FOREIGN KEY (`medewerker_id`)
    REFERENCES `forevertools`.`medewerker` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`voorraad_has_product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`voorraad_has_product` (
  `voorraad_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_bestelling_id` INT NOT NULL,
  PRIMARY KEY (`voorraad_id`, `product_id`, `product_bestelling_id`),
  INDEX `fk_voorraad_has_product_product1_idx` (`product_id` ASC, `product_bestelling_id` ASC) VISIBLE,
  INDEX `fk_voorraad_has_product_voorraad1_idx` (`voorraad_id` ASC) VISIBLE,
  CONSTRAINT `fk_voorraad_has_product_voorraad1`
    FOREIGN KEY (`voorraad_id`)
    REFERENCES `forevertools`.`voorraad` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_voorraad_has_product_product1`
    FOREIGN KEY (`product_id`, `product_bestelling_id`)
    REFERENCES `forevertools`.`product` (`id`, `bestelling_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `forevertools`.`product_has_bestelling`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `forevertools`.`product_has_bestelling` (
  `product_id` INT NOT NULL,
  `product_bestelling_id` INT NOT NULL,
  `bestelling_id` INT NOT NULL,
  `bestelling_klant_id` INT NOT NULL,
  `bestelling_medewerker_id` INT NOT NULL,
  PRIMARY KEY (`product_id`, `product_bestelling_id`, `bestelling_id`, `bestelling_klant_id`, `bestelling_medewerker_id`),
  INDEX `fk_product_has_bestelling_bestelling1_idx` (`bestelling_id` ASC, `bestelling_klant_id` ASC, `bestelling_medewerker_id` ASC) VISIBLE,
  INDEX `fk_product_has_bestelling_product1_idx` (`product_id` ASC, `product_bestelling_id` ASC) VISIBLE,
  CONSTRAINT `fk_product_has_bestelling_product1`
    FOREIGN KEY (`product_id`, `product_bestelling_id`)
    REFERENCES `forevertools`.`product` (`id`, `bestelling_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_has_bestelling_bestelling1`
    FOREIGN KEY (`bestelling_id`, `bestelling_klant_id`, `bestelling_medewerker_id`)
    REFERENCES `forevertools`.`bestelling` (`id`, `klant_id`, `medewerker_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Insert admin user if not exists
-- -----------------------------------------------------
INSERT INTO `forevertools`.`klant` (`voornaam`, `achternaam`, `admin`) 
SELECT 'Jordy', 'Meijer', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `forevertools`.`klant` 
    WHERE `voornaam` = 'Jordy' AND `achternaam` = 'Meijer'
)
LIMIT 1;

-- Update existing user to admin if they exist
UPDATE `forevertools`.`klant` 
SET `admin` = 1  
WHERE `voornaam` = 'Jordy' AND `achternaam` = 'Meijer';

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;