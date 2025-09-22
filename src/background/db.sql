SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`product` (
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
-- Table `mydb`.`locaties`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`locaties` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `adres` VARCHAR(100) NOT NULL,
  `postcode` VARCHAR(7) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`medewerker`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`medewerker` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `admin` TINYINT NOT NULL,
  `voornaam` VARCHAR(50) NOT NULL,
  `achternaam` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`bestelling`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`bestelling` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `totale_kosten` DECIMAL NOT NULL,
  `klant_id` INT NOT NULL,
  `medewerker_id` INT NOT NULL,
  PRIMARY KEY (`id`, `klant_id`, `medewerker_id`),
  INDEX `fk_bestelling_medewerker1_idx` (`medewerker_id` ASC) VISIBLE,
  CONSTRAINT `fk_bestelling_medewerker1`
    FOREIGN KEY (`medewerker_id`)
    REFERENCES `mydb`.`medewerker` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`product_has_locaties`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`product_has_locaties` (
  `product_id` INT NOT NULL,
  `product_bestelling_id` INT NOT NULL,
  `locaties_id` INT NOT NULL,
  `locaties_voorraad_id` INT NOT NULL,
  `aantal` VARCHAR(10000) NOT NULL,
  PRIMARY KEY (`product_id`, `product_bestelling_id`, `locaties_id`, `locaties_voorraad_id`),
  INDEX `fk_product_has_locaties_locaties1_idx` (`locaties_id` ASC, `locaties_voorraad_id` ASC) VISIBLE,
  INDEX `fk_product_has_locaties_product1_idx` (`product_id` ASC, `product_bestelling_id` ASC) VISIBLE,
  CONSTRAINT `fk_product_has_locaties_product1`
    FOREIGN KEY (`product_id` , `product_bestelling_id`)
    REFERENCES `mydb`.`product` (`id` , `bestelling_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_has_locaties_locaties1`
    FOREIGN KEY (`locaties_id`)
    REFERENCES `mydb`.`locaties` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`locaties_has_medewerker`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`locaties_has_medewerker` (
  `locaties_id` INT NOT NULL,
  `medewerker_id` INT NOT NULL,
  PRIMARY KEY (`locaties_id`, `medewerker_id`),
  INDEX `fk_locaties_has_medewerker_medewerker1_idx` (`medewerker_id` ASC) VISIBLE,
  INDEX `fk_locaties_has_medewerker_locaties1_idx` (`locaties_id` ASC) VISIBLE,
  CONSTRAINT `fk_locaties_has_medewerker_locaties1`
    FOREIGN KEY (`locaties_id`)
    REFERENCES `mydb`.`locaties` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_locaties_has_medewerker_medewerker1`
    FOREIGN KEY (`medewerker_id`)
    REFERENCES `mydb`.`medewerker` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`factuur`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`factuur` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `prijs` DECIMAL NOT NULL,
  `klantvoornaam` VARCHAR(50) NOT NULL,
  `klantachternaam` VARCHAR(50) NOT NULL,
  `bedrijfnaam` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`bestelling_has_factuur`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`bestelling_has_factuur` (
  `bestelling_id` INT NOT NULL,
  `bestelling_medewerker_id` INT NOT NULL,
  `factuur_id` INT NOT NULL,
  PRIMARY KEY (`bestelling_id`, `bestelling_medewerker_id`, `factuur_id`),
  INDEX `fk_bestelling_has_factuur_factuur1_idx` (`factuur_id` ASC) VISIBLE,
  INDEX `fk_bestelling_has_factuur_bestelling1_idx` (`bestelling_id` ASC, `bestelling_medewerker_id` ASC) VISIBLE,
  CONSTRAINT `fk_bestelling_has_factuur_bestelling1`
    FOREIGN KEY (`bestelling_id` , `bestelling_medewerker_id`)
    REFERENCES `mydb`.`bestelling` (`id` , `medewerker_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_bestelling_has_factuur_factuur1`
    FOREIGN KEY (`factuur_id`)
    REFERENCES `mydb`.`factuur` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`factuur_has_medewerker`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`factuur_has_medewerker` (
  `factuur_id` INT NOT NULL,
  `medewerker_id` INT NOT NULL,
  PRIMARY KEY (`factuur_id`, `medewerker_id`),
  INDEX `fk_factuur_has_medewerker_medewerker1_idx` (`medewerker_id` ASC) VISIBLE,
  INDEX `fk_factuur_has_medewerker_factuur1_idx` (`factuur_id` ASC) VISIBLE,
  CONSTRAINT `fk_factuur_has_medewerker_factuur1`
    FOREIGN KEY (`factuur_id`)
    REFERENCES `mydb`.`factuur` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_factuur_has_medewerker_medewerker1`
    FOREIGN KEY (`medewerker_id`)
    REFERENCES `mydb`.`medewerker` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`voorraad`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`voorraad` (
  `id` INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`voorraad_has_product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`voorraad_has_product` (
  `voorraad_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_bestelling_id` INT NOT NULL,
  PRIMARY KEY (`voorraad_id`, `product_id`, `product_bestelling_id`),
  INDEX `fk_voorraad_has_product_product1_idx` (`product_id` ASC, `product_bestelling_id` ASC) VISIBLE,
  INDEX `fk_voorraad_has_product_voorraad1_idx` (`voorraad_id` ASC) VISIBLE,
  CONSTRAINT `fk_voorraad_has_product_voorraad1`
    FOREIGN KEY (`voorraad_id`)
    REFERENCES `mydb`.`voorraad` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_voorraad_has_product_product1`
    FOREIGN KEY (`product_id` , `product_bestelling_id`)
    REFERENCES `mydb`.`product` (`id` , `bestelling_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`product_has_bestelling`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`product_has_bestelling` (
  `product_id` INT NOT NULL,
  `product_bestelling_id` INT NOT NULL,
  `bestelling_id` INT NOT NULL,
  `bestelling_klant_id` INT NOT NULL,
  `bestelling_medewerker_id` INT NOT NULL,
  PRIMARY KEY (`product_id`, `product_bestelling_id`, `bestelling_id`, `bestelling_klant_id`, `bestelling_medewerker_id`),
  INDEX `fk_product_has_bestelling_bestelling1_idx` (`bestelling_id` ASC, `bestelling_klant_id` ASC, `bestelling_medewerker_id` ASC) VISIBLE,
  INDEX `fk_product_has_bestelling_product1_idx` (`product_id` ASC, `product_bestelling_id` ASC) VISIBLE,
  CONSTRAINT `fk_product_has_bestelling_product1`
    FOREIGN KEY (`product_id` , `product_bestelling_id`)
    REFERENCES `mydb`.`product` (`id` , `bestelling_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_has_bestelling_bestelling1`
    FOREIGN KEY (`bestelling_id` , `bestelling_klant_id` , `bestelling_medewerker_id`)
    REFERENCES `mydb`.`bestelling` (`id` , `klant_id` , `medewerker_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;