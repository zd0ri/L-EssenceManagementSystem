-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 02:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `essence_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_id`, `name`, `description`, `image`, `created_at`) VALUES
(5, 'Valentino', 'Embodies modern romance and Italian couture—luxurious, bold, and effortlessly stylish with a touch of youthful energy.', 'uploads/brands/brand_1763495354_edefa3c9dc90.png', '2025-11-19 03:49:14'),
(6, 'Jo Malone', 'Offers fresh, clean, and beautifully layered fragrances that can be worn alone or combined for a personalized scent experience.', 'uploads/brands/brand_1763495393_d97bac631c09.png', '2025-11-19 03:49:53'),
(7, 'Dior', 'Timeless and sophisticated, blending classic French elegance with innovative compositions that leave a powerful, lasting impression.', 'uploads/brands/brand_1763495422_4711b6ef61fb.png', '2025-11-19 03:50:22'),
(10, 'Dolce & Gabbana', 'A blend of Sicilian charm and modern sensuality, Dolce & Gabbana fragrances are bold, passionate, and inspired by Mediterranean elegance.', 'uploads/brands/brand_1763530495_01ed914bc9dd.png', '2025-11-19 13:34:55'),
(11, 'Burberry', 'Burberry perfumes capture British sophistication—clean, classic, and effortlessly timeless with warm, comforting notes.', 'uploads/brands/brand_1763530590_b684c0f11398.png', '2025-11-19 13:36:30');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date_registered` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `fullname`, `contact`, `address`, `email`, `date_registered`) VALUES
(1, 4, 'iya', '099999999', 'taguig', 'iya@gmail.com', '2025-11-10 14:45:21'),
(2, 8, 'Rajhzkar Vzourlgh', '8700', 'Pennysylvania', 'raj@gmail.com', '2025-11-19 11:05:27');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `expense_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date_recorded` datetime DEFAULT current_timestamp(),
  `recorded_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `restock_date` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `product_id`, `quantity`, `last_updated`, `restock_date`, `updated_by`) VALUES
(13, 13, 46, '2025-11-19 05:59:44', '2025-11-19 04:59:32', 6),
(14, 14, 35, '2025-11-19 07:03:13', '2025-11-19 07:03:13', 6),
(15, 15, 35, '2025-11-19 07:07:32', '2025-11-19 07:07:32', 6),
(16, 16, 35, '2025-11-19 07:10:37', '2025-11-19 07:10:37', 6),
(17, 17, 35, '2025-11-19 07:13:02', '2025-11-19 07:13:02', 6),
(18, 18, 35, '2025-11-19 13:01:41', '2025-11-19 13:01:41', 6),
(19, 19, 35, '2025-11-19 13:03:53', '2025-11-19 13:03:53', 6),
(20, 20, 35, '2025-11-19 13:05:35', '2025-11-19 13:05:35', 6),
(21, 21, 1, '2025-11-19 13:21:47', '2025-11-19 13:21:47', 6),
(22, 22, 35, '2025-11-19 13:12:40', '2025-11-19 13:12:40', 6),
(23, 23, 35, '2025-11-19 13:14:45', '2025-11-19 13:14:45', 6),
(24, 24, 35, '2025-11-19 13:18:40', '2025-11-19 13:18:40', 6),
(25, 25, 35, '2025-11-19 13:40:37', '2025-11-19 13:40:37', 6),
(26, 26, 35, '2025-11-19 13:43:27', '2025-11-19 13:43:27', 6),
(27, 27, 35, '2025-11-19 13:46:13', '2025-11-19 13:46:13', 6),
(28, 28, 35, '2025-11-19 13:48:17', '2025-11-19 13:48:17', 6),
(29, 29, 1, '2025-11-19 13:50:34', '2025-11-19 13:50:34', 6),
(30, 30, 35, '2025-11-19 13:53:32', '2025-11-19 13:53:32', 6),
(31, 31, 35, '2025-11-19 13:55:34', '2025-11-19 13:55:34', 6),
(32, 32, 1, '2025-11-19 13:57:34', '2025-11-19 13:57:34', 6),
(33, 33, 35, '2025-11-19 14:04:39', '2025-11-19 14:04:39', 6);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','processing','shipped','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `delivery_method` varchar(100) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `stock_reduced` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `total_amount`, `order_date`, `status`, `payment_status`, `delivery_method`, `remarks`, `stock_reduced`) VALUES
(8, 1, 10200.00, '2025-11-19 05:03:04', 'shipped', 'paid', 'Standard', 'Sana po may freebie', 1),
(9, 1, 30600.00, '2025-11-19 05:43:11', 'completed', 'paid', 'Standard', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_each` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `price_each`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price_each`) VALUES
(8, 8, 13, 1, 10200.00),
(9, 9, 13, 3, 10200.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('GCash','Credit Card','Cash on Delivery','Bank Transfer') NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `date_paid` datetime DEFAULT current_timestamp(),
  `reference_no` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_method`, `amount_paid`, `date_paid`, `reference_no`) VALUES
(4, 8, 'GCash', 10200.00, '2025-11-19 05:03:04', NULL),
(5, 9, 'GCash', 30600.00, '2025-11-19 05:43:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `scent_type` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('available','out_of_stock','discontinued') DEFAULT 'available',
  `date_added` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `brand_id`, `product_name`, `brand_name`, `scent_type`, `size`, `price`, `description`, `image`, `status`, `date_added`) VALUES
(13, 6, 'Wood Sage & Sea Salt', 'Wood Sage & Sea Salt', 'Aromatic', '50', 10200.00, 'Evokes a breezy coastal walk — sea salt, sage, and soft ambrette for a relaxed, natural feel.', '', 'available', '2025-11-19 04:00:52'),
(14, 6, 'English Pear & Freesia Cologne', '', 'Fruity-floral', '100', 3970.00, 'Juicy pear blended with delicate white freesia, grounded with soft woods for an elegant, understated scent.', '', 'available', '2025-11-19 07:03:13'),
(15, 6, 'Peony & Blush Suede Cologne', '', 'Floral', '100', 4450.00, 'Soft, romantic floral of peony and rose, with a hint of red apple and smooth suede for warmth.', '', 'available', '2025-11-19 07:07:32'),
(16, 5, 'Uomo Born In Roma', '', 'Woody', '100', 7250.00, 'A modern masculine scent inspired by Roman architecture; violet leaf, sage, and mineral salt evoke a city’s night breeze.', '', 'available', '2025-11-19 07:10:37'),
(17, 5, 'Uomo Born In Roma Intense', '', 'Oriental', '100', 7000.00, 'A richer, more seductive take on the original — vanilla bourbon infusion, lavender, and smoky vetiver.', '', 'available', '2025-11-19 07:13:02'),
(18, 5, 'Donna Born In Roma EDP', '', 'Floral', '100', 9200.00, 'Feminine and modern — notes of blackcurrant, pink pepper, and bergamot with a heart of jasmine and a vanilla-wood base.', '', 'available', '2025-11-19 13:01:41'),
(19, 5, 'Donna Born In Roma Intense EDP', '', 'Amber Floral', '100', 11650.00, 'A deeper, warmer version of the original, with resinous and amber-rich undertones.', '', 'available', '2025-11-19 13:03:53'),
(20, 5, 'Donna Born In Roma Yellow Dream EDP', '', 'Citrus', '100', 10600.00, 'Light, airy, and bright — lemon, fresh musk, and rose combine for a dreamy, youthful feel.', '', 'available', '2025-11-19 13:05:35'),
(21, 7, 'J’adore Eau de Parfum', '', 'Fruity-Floral', '100', 4500.00, 'A luxurious floral bouquet of ylang-ylang, Damascus rose, and jasmine — elegantly feminine and timeless.', '', 'available', '2025-11-19 13:10:47'),
(22, 7, 'Sauvage Parfum', '', 'Aromatic', '100', 4500.00, 'A sophisticated, more intense version of the iconic Sauvage: spicy grapefruit and lavender balanced with deep woods.', '', 'available', '2025-11-19 13:12:40'),
(23, 7, 'Miss Dior Eau de Parfum', '', 'Floral', '100', 8000.00, 'A fresh yet elegant floral scent: rose, jasmine, and patchouli, capturing the classic spirit of Dior.', '', 'available', '2025-11-19 13:14:45'),
(24, 7, 'J’adore Infinissime', '', 'Woody-Floral', '50', 6850.00, 'A more concentrated, woody-floral interpretation of J’adore, with amplified jasmine and a warmer dry-down.', '', 'available', '2025-11-19 13:18:40'),
(25, 10, 'Light Blue (Women)', '', 'Citrus', '100', 2600.00, 'A sun-kissed Mediterranean scent with crisp lemon, green apple, and soft floral notes — very breezy and beachy.', '', 'available', '2025-11-19 13:40:37'),
(26, 10, 'The One (Men) Eau de Parfum', '', 'Woody-Spicy', '100', 5000.00, 'A warm, elegant fragrance — grapefruit, basil, and cardamom top; a heart of ginger and orange blossom; base of amber and tobacco.', '', 'available', '2025-11-19 13:43:27'),
(27, 10, 'My Devotion Eau de Parfum Intense', '', 'Fruity-Gourmand', '100', 12400.00, 'A romantic, rich women’s scent — pear, florals, caramel, and warm woods.', '', 'available', '2025-11-19 13:46:13'),
(28, 10, 'Intense Eau de Parfum', '', 'Woody-Amber', '100', 6900.00, 'Bold and confident; this version of “K” has bergamot, incense, and a rich amber-wood base — regal and strong.', '', 'available', '2025-11-19 13:48:17'),
(29, 10, 'Pour Homme Intenso Eau de Parfum', '', 'Fougère-Woody', '100', 5500.00, 'Deep and refined with a mix of lavender, basil, tobacco, hay, and soft woods — clean but bold.', '', 'available', '2025-11-19 13:50:34'),
(30, 11, 'Her Eau de Parfum', '', 'Gourmand-Fruity', '100', 7000.00, 'A youthful, sweet berry scent — strawberry, raspberry, and blackcurrant, balanced by warm amber and musk.', '', 'available', '2025-11-19 13:53:32'),
(31, 11, 'Goddess Eau de Parfum Intense', '', 'Vanila-Gourmand', '', 8500.00, 'A rich, luxurious take on vanilla — three types of vanilla layered with lavender and a hint of spice.', '', 'available', '2025-11-19 13:55:34'),
(32, 11, 'London Eau de Parfum', '', 'Floral-Woody', '100', 3400.00, 'Sophisticated floral scent — rose, honeysuckle, tangerine, and a soft, cozy vanilla-musk finish.', '', 'available', '2025-11-19 13:57:34'),
(33, 11, 'My Burberry Eau de Parfum', '', 'Floral-Green', '100', 8900.00, 'Inspired by a London garden after the rain — sweet pea, bergamot, freesia, golden quince, and patchouli.', '', 'available', '2025-11-19 14:04:39');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `product_image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`product_image_id`, `product_id`, `path`, `uploaded_at`) VALUES
(1, 2, 'uploads/products/prod_2_69118e20c0c07.png', '2025-11-10 15:02:56'),
(2, 2, 'uploads/products/prod_2_69118e20c1a85.jpg', '2025-11-10 15:02:56'),
(3, 2, 'uploads/products/prod_2_69118e20c25bf.jpg', '2025-11-10 15:02:56'),
(4, 3, 'uploads/products/prod_3_69119af03ef5b.jpg', '2025-11-10 15:57:36'),
(5, 3, 'uploads/products/prod_3_69119af03fa54.jpg', '2025-11-10 15:57:36'),
(6, 3, 'uploads/products/prod_3_69119af040589.jpg', '2025-11-10 15:57:36'),
(7, 4, 'uploads/products/prod_4_69119b07d87cc.jpg', '2025-11-10 15:57:59'),
(8, 4, 'uploads/products/prod_4_69119b07d9444.png', '2025-11-10 15:57:59'),
(9, 5, 'uploads/products/prod_5_691577dd1eeef.jpg', '2025-11-13 14:17:01'),
(10, 6, 'uploads/products/prod_6_6919f27657d7b.jpg', '2025-11-16 23:49:10'),
(11, 7, 'uploads/products/prod_7_691b60d7257e1.jpg', '2025-11-18 01:52:23'),
(12, 8, 'uploads/products/prod_8_691c5c862ebaf.png', '2025-11-18 19:46:14'),
(13, 8, 'uploads/products/prod_8_691c5c862f70f.png', '2025-11-18 19:46:14'),
(14, 8, 'uploads/products/prod_8_691c5c86300ff.png', '2025-11-18 19:46:14'),
(15, 9, 'uploads/products/prod_9_691c5d2bb80eb.png', '2025-11-18 19:48:59'),
(16, 10, 'uploads/products/prod_10_691c5e3cabafc.png', '2025-11-18 19:53:32'),
(17, 10, 'uploads/products/prod_10_691c5e3cac493.png', '2025-11-18 19:53:32'),
(18, 11, 'uploads/products/prod_11_691c5ed092dab.png', '2025-11-18 19:56:00'),
(19, 11, 'uploads/products/prod_11_691c5ed093a34.png', '2025-11-18 19:56:00'),
(20, 12, 'uploads/products/prod_12_691c5f074df20.png', '2025-11-18 19:56:55'),
(21, 12, 'uploads/products/prod_12_691c5f074ea20.png', '2025-11-18 19:56:55'),
(22, 13, 'uploads/products/prod_13_691cd0740ca8d.jpg', '2025-11-19 04:00:52'),
(23, 14, 'uploads/products/prod_14_691cfb3131da5.jpg', '2025-11-19 07:03:13'),
(24, 15, 'uploads/products/prod_15_691cfc343d8a4.png', '2025-11-19 07:07:32'),
(25, 16, 'uploads/products/prod_16_691cfced15d12.png', '2025-11-19 07:10:37'),
(26, 17, 'uploads/products/prod_17_691cfd7e3556e.png', '2025-11-19 07:13:02'),
(27, 18, 'uploads/products/prod_18_691d4f353fc67.jpg', '2025-11-19 13:01:41'),
(28, 19, 'uploads/products/prod_19_691d4fb9c50d8.png', '2025-11-19 13:03:53'),
(29, 20, 'uploads/products/prod_20_691d501faaa10.jpg', '2025-11-19 13:05:35'),
(31, 22, 'uploads/products/prod_22_691d51c844c17.jpg', '2025-11-19 13:12:40'),
(32, 23, 'uploads/products/prod_23_691d5245ac58c.jpg', '2025-11-19 13:14:45'),
(33, 24, 'uploads/products/prod_24_691d53306cf59.png', '2025-11-19 13:18:40'),
(34, 21, 'uploads/products/prod_21_691d53eb0bb93.png', '2025-11-19 13:21:47'),
(35, 25, 'uploads/products/prod_25_691d5855d4c63.jpg', '2025-11-19 13:40:37'),
(36, 26, 'uploads/products/prod_26_691d58ff7ce29.png', '2025-11-19 13:43:27'),
(37, 27, 'uploads/products/prod_27_691d59a536682.png', '2025-11-19 13:46:13'),
(38, 28, 'uploads/products/prod_28_691d5a21da633.png', '2025-11-19 13:48:17'),
(39, 29, 'uploads/products/prod_29_691d5aaa68a01.jpg', '2025-11-19 13:50:34'),
(40, 30, 'uploads/products/prod_30_691d5b5c7abfa.png', '2025-11-19 13:53:32'),
(41, 31, 'uploads/products/prod_31_691d5bd67186b.jpg', '2025-11-19 13:55:34'),
(42, 32, 'uploads/products/prod_32_691d5c4e159cc.jpg', '2025-11-19 13:57:34'),
(43, 33, 'uploads/products/prod_33_691d5df790465.jpg', '2025-11-19 14:04:39');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `review_text` text DEFAULT NULL,
  `review_image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `product_id`, `user_id`, `customer_id`, `rating`, `review_text`, `review_image`, `created_at`, `updated_at`) VALUES
(1, 3, 4, 1, 4, 'thank you', NULL, '2025-11-13 15:43:32', '2025-11-13 15:44:21'),
(5, 2, 4, 1, 5, 'nice one', NULL, '2025-11-13 17:28:24', '2025-11-13 17:28:24'),
(7, 2, 4, 1, 5, 'fngdfj', 'uploads/reviews/1763026738_f121be6b2812.jpg', '2025-11-13 17:38:58', '2025-11-13 17:38:58'),
(8, 2, 4, 1, 5, 'Ganda', 'uploads/reviews/1763401661_d954bacb4e5b.jpg', '2025-11-18 01:47:41', '2025-11-18 01:47:41'),
(9, 2, 4, 1, 5, 'Ganda', 'uploads/reviews/1763401780_46e091f7e228.jpg', '2025-11-18 01:49:40', '2025-11-18 01:49:40'),
(10, 2, 4, 1, 5, 'Oki', NULL, '2025-11-18 01:50:26', '2025-11-18 01:50:26');

-- --------------------------------------------------------

--
-- Table structure for table `sales_report`
--

CREATE TABLE `sales_report` (
  `report_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `total_expenses` decimal(10,2) NOT NULL,
  `net_income` decimal(10,2) GENERATED ALWAYS AS (`total_sales` - `total_expenses`) STORED,
  `generated_by` int(11) DEFAULT NULL,
  `date_generated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer','inventory_manager','finance_manager') NOT NULL,
  `email` varchar(150) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `email`, `status`, `date_created`) VALUES
(1, 'meriel', '9c79d5ffda33b3737b9fa2144ad2ea5cff2d41b9', 'customer', 'meriel@gmail.com', 'active', '2025-11-07 21:51:52'),
(4, 'iya', '5e0793b84942a9d77f39a3859020ce9871262418', 'customer', 'iya@gmail.com', 'active', '2025-11-10 14:44:59'),
(6, 'admin', '6052acf657148ec39725c596e25bd0612fd301a6', 'admin', 'admin@example.com', 'active', '2025-11-10 14:58:59'),
(7, 'brianna', '89cede06a683be1c9425ae98d23d188392b45537', 'customer', 'brianna@gmail.com', 'inactive', '2025-11-18 15:51:02'),
(8, 'raj', '32b0d91174b1d96e251ce8f0b8fdf370fdbcf74b', 'customer', 'raj@gmail.com', 'active', '2025-11-19 11:02:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`product_image_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`);

--
-- Indexes for table `sales_report`
--
ALTER TABLE `sales_report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `product_image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sales_report`
--
ALTER TABLE `sales_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL ON UPDATE CASCADE;


ALTER TABLE `sales_report`
  ADD CONSTRAINT `sales_report_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

--
-- Constraints for table `sales_report`
--

CREATE VIEW order_transaction_view AS SELECT o.order_id, oi.product_id, p.product_name, oi.quantity, oi.price_each, 
(oi.quantity * oi.price_each) AS line_subtotal, o.total_amount, o.order_date, c.fullname, c.email 
FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN products p ON oi.product_id = 
p.product_id JOIN customers c ON o.customer_id = c.customer_id;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
