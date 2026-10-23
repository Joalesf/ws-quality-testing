CREATE DATABASE IF NOT EXISTS bus_web_services
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bus_web_services;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(500) NOT NULL,
  category VARCHAR(60) NOT NULL,
  protocol ENUM('REST', 'SOAP') NOT NULL,
  endpoint VARCHAR(255) NOT NULL,
  documentation_url VARCHAR(255) NOT NULL,
  http_method VARCHAR(20) NOT NULL DEFAULT 'GET',
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_service_endpoint (endpoint),
  INDEX idx_service_search (status, protocol, category),
  FULLTEXT INDEX idx_service_text (name, description, category)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS search_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  search_term VARCHAR(120) NOT NULL DEFAULT '',
  category VARCHAR(60) NOT NULL DEFAULT '',
  protocol VARCHAR(10) NOT NULL DEFAULT '',
  result_count INT UNSIGNED NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) NOT NULL DEFAULT 'local',
  searched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_search_date (searched_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS test_cases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  module VARCHAR(100) NOT NULL,
  requirement_code VARCHAR(30) NOT NULL DEFAULT '',
  requirement_type ENUM('Funcional', 'No funcional') NOT NULL DEFAULT 'No funcional',
  technique ENUM('Caja blanca', 'Caja negra') NOT NULL,
  subtechnique VARCHAR(100) NOT NULL DEFAULT '',
  objective VARCHAR(800) NOT NULL,
  preconditions VARCHAR(1200) NOT NULL,
  input_data VARCHAR(1200) NOT NULL,
  steps TEXT NOT NULL,
  expected_result VARCHAR(1200) NOT NULL,
  actual_result VARCHAR(1200) NOT NULL DEFAULT '',
  status ENUM('Pendiente', 'Éxito', 'Fallo', 'Bloqueado') NOT NULL DEFAULT 'Pendiente',
  priority ENUM('Alta', 'Media', 'Baja') NOT NULL DEFAULT 'Media',
  responsible VARCHAR(100) NOT NULL,
  execution_date DATE NOT NULL,
  environment VARCHAR(150) NOT NULL DEFAULT '',
  evidence VARCHAR(500) NOT NULL DEFAULT '',
  observations VARCHAR(1200) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_test_case_date (execution_date),
  INDEX idx_test_case_status (status, technique)
) ENGINE=InnoDB;

INSERT IGNORE INTO services (name, description, category, protocol, endpoint, documentation_url, http_method) VALUES
('JSONPlaceholder', 'API REST de prueba para publicaciones, usuarios, comentarios y tareas.', 'Desarrollo', 'REST', 'https://jsonplaceholder.typicode.com/posts', 'https://jsonplaceholder.typicode.com/', 'GET'),
('REST Countries', 'Información pública de países, capitales, monedas, idiomas y regiones.', 'Geografía', 'REST', 'https://restcountries.com/v3.1/all', 'https://restcountries.com/', 'GET'),
('Open Meteo', 'Pronóstico meteorológico por coordenadas sin clave para usos no comerciales.', 'Clima', 'REST', 'https://api.open-meteo.com/v1/forecast', 'https://open-meteo.com/en/docs', 'GET'),
('PokeAPI', 'Datos de Pokémon, habilidades, especies, movimientos y recursos relacionados.', 'Entretenimiento', 'REST', 'https://pokeapi.co/api/v2/pokemon', 'https://pokeapi.co/docs/v2', 'GET'),
('Open Library', 'Búsqueda de libros, autores y ediciones del catálogo de Open Library.', 'Educación', 'REST', 'https://openlibrary.org/search.json', 'https://openlibrary.org/developers/api', 'GET'),
('World Bank Indicators', 'Indicadores de desarrollo y series estadísticas del Banco Mundial.', 'Economía', 'REST', 'https://api.worldbank.org/v2/country/all/indicator/SP.POP.TOTL?format=json', 'https://datahelpdesk.worldbank.org/knowledgebase/topics/125589-developer-information', 'GET'),
('Cat Facts', 'API pública sencilla que devuelve datos curiosos sobre gatos.', 'Entretenimiento', 'REST', 'https://catfact.ninja/fact', 'https://catfact.ninja/', 'GET'),
('Numbers API', 'Información matemática, histórica o curiosa asociada a números.', 'Educación', 'REST', 'http://numbersapi.com/42', 'http://numbersapi.com/', 'GET'),
('Country Info Service', 'Servicio SOAP para consultar países, monedas, idiomas y códigos internacionales.', 'Geografía', 'SOAP', 'http://webservices.oorsprong.org/websamples.countryinfo/CountryInfoService.wso', 'https://www.oorsprong.org/websamples.countryinfo/CountryInfoService.wso', 'POST'),
('Number Conversion Service', 'Servicio SOAP de demostración que convierte números a palabras o monedas.', 'Utilidades', 'SOAP', 'https://www.dataaccess.com/webservicesserver/NumberConversion.wso', 'https://www.dataaccess.com/webservicesserver/NumberConversion.wso', 'POST');
