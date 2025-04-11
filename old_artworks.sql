-- --------------------------------------------------------

--
-- Table structure for table `old_artworks`
--

CREATE TABLE `old_artworks` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist_name` varchar(255) NOT NULL,
  `year` varchar(50) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `medium` varchar(255) DEFAULT NULL,
  `image_url` varchar(512) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 
 
 
 
 