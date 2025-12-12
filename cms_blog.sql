-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 10:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cms_blog`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(4, 'Booth Banter', 'booth-banter', '', '2025-11-14 06:37:53', '2025-11-14 06:37:53'),
(5, 'Photo Booth', 'photo-booth', '', '2025-11-14 06:38:07', '2025-11-14 06:38:07'),
(6, 'Photo Booth Events', 'photo-booth-events', '', '2025-11-14 06:38:20', '2025-11-14 06:38:20'),
(7, 'Phoenix', 'phoenix', '', '2025-11-14 06:38:33', '2025-11-14 06:38:33'),
(8, 'Denver', 'denver', '', '2025-11-14 06:38:39', '2025-11-14 06:38:39'),
(9, 'Ai Photo Booth', 'ai-photo-booth', '', '2025-11-16 07:33:14', '2025-11-16 07:33:14'),
(10, 'Halloween', 'halloween', '', '2025-11-16 07:33:30', '2025-11-16 07:33:30'),
(11, 'Christmas Booth', 'christmas-booth', '', '2025-11-16 07:33:39', '2025-11-16 07:33:39'),
(12, 'Aspen', 'aspen', '', '2025-11-16 07:33:45', '2025-11-16 07:33:45'),
(13, 'Colorado', 'colorado', '', '2025-11-16 07:33:51', '2025-11-16 07:33:51'),
(14, 'Arizona', 'arizona', '', '2025-11-16 07:34:09', '2025-11-16 07:34:09'),
(15, '360 Video Booth', '360-video-booth', '', '2025-11-16 07:34:24', '2025-11-16 07:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content_html` mediumtext DEFAULT NULL,
  `custom_css` mediumtext DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `category_id`, `author_id`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(10, 'Thomas Bennett House – Charleston Venues', 'thomas-bennett-house-charleston-venues', '<h2>Let us Host your Party</h2><p><br>&nbsp;</p><p>Parties don’t just start at the time you decide, they start when the planning does. That’s where Thomas Bennett House comes into play. This beautiful venue, and its professional staff are waiting to help you every step of the way. No matter what your party is, a roaring 20’s, Gatsby party, or your wedding, the atmosphere and surroundings will be the perfect match.</p><p>Speaking of perfect match, take a look at the <a href=\"https://mihiphotobooth.com/charleston-sc\"><strong>Charleston photo booth</strong></a>. The vintage look of the booth brings you back in time, and completes that 20’s feel. Don’t worry though, it is also elegant and timeless so it will fit with your wedding needs as well.</p><h2>On the Outside&nbsp;<br>&nbsp;</h2><p>Multiple outdoor areas are available at <strong>Thomas Bennett House</strong>. With stunning garden areas to the manicured lawns, you will love taking in the southern breezes. The other outdoor areas here are stunning and waiting for you as well. Whether you are planning on a small or large attendance for a Gatsby party or your reception, the spaces were made with you in mind and everyone will fit wonderfully.</p><p>Those outdoor areas lend themselves to you for lovely backdrops. Imagine your guests in the best time period attire posing for your <i>Charleston photo booth</i> in the lawn capturing the surroundings as well. Maybe your wedding party in the courtyard, with a snap of the camera, our perfect pictures are taken.</p><p>&nbsp;</p><p><br>&nbsp;</p><p><br>&nbsp;</p><p>&nbsp;</p><p><img src=\"https://static.wixstatic.com/media/a92b01_ac60cc5efccc4eabb72b2c8794502bbe~mv2.jpg/v1/fill/w_560,h_374,al_c,lg_1,q_80,enc_avif,quality_auto/a92b01_ac60cc5efccc4eabb72b2c8794502bbe~mv2.jpg\" alt=\"ThomasBennett2.jpg\" width=\"740\" height=\"493\"></p><p>&nbsp;</p><p><br>&nbsp;</p><p><br>&nbsp;</p><p>&nbsp;</p><p><img src=\"https://static.wixstatic.com/media/a92b01_0a9395c8d8334061a4dace5d7331c3e1~mv2.jpg/v1/fill/w_560,h_374,al_c,lg_1,q_80,enc_avif,quality_auto/a92b01_0a9395c8d8334061a4dace5d7331c3e1~mv2.jpg\" alt=\"ThomasBennett3.jpg\" width=\"740\" height=\"493\"></p><p>&nbsp;</p><p><br>&nbsp;</p><p><br>&nbsp;</p><p>&nbsp;</p><p><img src=\"https://static.wixstatic.com/media/a92b01_c6cc6061b4af4fcd95fcc33bb39acd73~mv2.jpg/v1/fill/w_560,h_328,al_c,lg_1,q_80,enc_avif,quality_auto/a92b01_c6cc6061b4af4fcd95fcc33bb39acd73~mv2.jpg\" alt=\"ThomasBennett.jpg\" width=\"740\" height=\"432\"></p><p>&nbsp;</p><h2>Come on inside</h2><p><br>&nbsp;</p><p>When you first walk inside, you will feel like you have been transported back in time. Being met by stunning columns, is only the beginning. Gorgeous marble mantels, that will take your breath away. A very unique touch here is the staircase. We know what you are thinking, what could be so special about a staircase? It is a magnificent floating staircase. Such a stunning setting for your 20’s party, or the celebration of your big day.</p><p>Your <strong>Charleston photo booth</strong> will capture the details of your surroundings beautifully. Your guests, props, poses, and memories all become a photo to be cherished for years to come. This is a great way to add fun and excitement to your themed party or reception.</p><h2>All the Help You May Need&nbsp;<br>&nbsp;</h2><p>You may have questions, or concerns about achieving your ideal outcome. Rest assured the staff at <strong>Thomas Bennett House</strong> will be able to help guide you in the right direction. They know about having fun, and making your event as great as you imagined! It is what is important to them as well.</p><p>A Charleston photo booth screams fun, and the staff will be there to show you just how much fun can be had. Not only can they add on some fun, personal touches, but the attendant at your party can help to. From props to fun poses and more, all to make your photos, well as glamorous as your party!</p><p><strong>It’s Time to Get Your Party Planned </strong>We are waiting for you and your party, so let’s get started!</p><ul><li><a href=\"https://www.mihiphotobooth.com/blog/categories/charleston\">Charleston</a></li></ul>', '', 'http://localhost/cms/uploads/images/thumb_6916cf3bbb83e2.25044423.jpg', 5, 1, 'published', 0, '2025-11-14 06:40:00', '2025-11-14 06:44:45', '2025-11-14 06:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_tags`
--

INSERT INTO `post_tags` (`post_id`, `tag_id`) VALUES
(10, 5),
(10, 6);

-- --------------------------------------------------------

--
-- Table structure for table `seo_metadata`
--

CREATE TABLE `seo_metadata` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `twitter_card` varchar(50) DEFAULT 'summary_large_image',
  `canonical_url` varchar(255) DEFAULT NULL,
  `robots` varchar(100) DEFAULT 'index, follow',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seo_metadata`
--

INSERT INTO `seo_metadata` (`id`, `post_id`, `meta_title`, `meta_description`, `meta_keywords`, `og_title`, `og_description`, `og_image`, `twitter_card`, `canonical_url`, `robots`, `created_at`, `updated_at`) VALUES
(10, 10, 'Thomas Bennett House – Charleston Venues', 'Photos from venue website: governorthomasbennetthouse.com Let us Host your Party Parties don’t just start at the time you decide, they start when the planning does. That’s where Thomas Bennett House comes into play. This beautiful venue, and its professional staff are waiting to help you every step of the way. No matter what your party is, a roaring 20’s, Gatsby party, or your wedding, the atmosphere and surroundings will be the perfect match. Speaking of perfect match, take a look at the Charles.', '', 'Thomas Bennett House – Charleston Venues', '', '', 'summary_large_image', '', 'index, follow', '2025-11-14 06:44:45', '2025-11-14 06:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'My Blog', '2025-11-13 04:21:03'),
(2, 'site_description', 'A modern blog with SEO integration', '2025-11-13 04:21:03'),
(3, 'site_url', 'http://localhost/cms', '2025-11-13 04:21:03'),
(4, 'default_meta_description', 'Welcome to our blog', '2025-11-13 04:21:03'),
(5, 'default_meta_keywords', 'blog, articles, news', '2025-11-13 04:21:03');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `created_at`) VALUES
(4, '#ArtistFirst', 'artistfirst', '2025-11-14 03:52:36'),
(5, '#FutureOfEntertainment', 'futureofentertainment', '2025-11-14 03:52:49'),
(6, '#NewMedia', 'newmedia', '2025-11-14 03:53:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','editor') DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$liTf/PEhs9es.d14rjhxlOaUDAXN2cAdGDIB6QSQIJvLQ6WRVJtEC', 'Administrator', 'admin', '2025-11-13 04:21:03', '2025-11-13 04:21:03'),
(2, 'bladekaizertron', 'jedlanzarote@code-rebuilt.com', '$2y$10$UM72X0y21Fylc7LIt3F8/eZwGnhJGBON9s/xuG1Hu9kmE4OE9sT.G', 'JED LANZAROTE', 'editor', '2025-11-16 04:20:38', '2025-11-16 04:31:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_pages_status` (`status`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_published_at` (`published_at`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `seo_metadata`
--
ALTER TABLE `seo_metadata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_post_seo` (`post_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `seo_metadata`
--
ALTER TABLE `seo_metadata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seo_metadata`
--
ALTER TABLE `seo_metadata`
  ADD CONSTRAINT `seo_metadata_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
