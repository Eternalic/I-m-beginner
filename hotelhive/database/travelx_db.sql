-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-04-15 03:10:58
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `travelx_db`
--

-- --------------------------------------------------------

--
-- 表的结构 `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(50) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `admins`
--

INSERT INTO `admins` (`admin_id`, `admin_name`, `admin_password`, `created_at`) VALUES
(3, 'Unknown', '$2y$10$xKN.UrN3NcM/WBVzZluLK.Qw6y0ds7QKGwK6//3.wVYMRnZ6oKvZ2', '2025-04-03 07:40:48');

-- --------------------------------------------------------

--
-- 表的结构 `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `book_number` varchar(50) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_requests` varchar(255) DEFAULT NULL,
  `late_checkout_time` varchar(20) DEFAULT NULL,
  `room_service_package` varchar(50) DEFAULT NULL,
  `booking_status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `bookings`
--

INSERT INTO `bookings` (`booking_id`, `hotel_id`, `user_id`, `room_id`, `book_number`, `check_in_date`, `check_out_date`, `total_price`, `special_requests`, `late_checkout_time`, `room_service_package`, `booking_status`, `created_at`) VALUES
(155, 1, 5, 1, '9NU4U', '2025-04-09', '2025-04-10', 20.90, 'non-smoking', '12:00', 'none', 'confirmed', '2025-04-09 01:53:33'),
(156, 2, 5, 3, 'T96MS', '2025-04-10', '2025-04-11', 33.00, 'non-smoking', '12:00', 'none', 'confirmed', '2025-04-10 07:48:16'),
(157, 3, 5, 5, 'ZM9J6', '2025-04-10', '2025-04-11', 33.00, 'non-smoking', '12:00', 'none', 'confirmed', '2025-04-10 07:49:06');

-- --------------------------------------------------------

--
-- 表的结构 `country_img`
--

CREATE TABLE `country_img` (
  `ci_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `country_img` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `country_img`
--

INSERT INTO `country_img` (`ci_id`, `hotel_id`, `country_img`, `created_at`) VALUES
(1, 1, 'images/country/malaysia.jpg', '2025-04-09 06:15:00'),
(2, 2, 'images/country/malaysia.jpg', '2025-04-09 06:15:00'),
(3, 3, 'images/country/malaysia.jpg', '2025-04-09 06:15:00');

-- --------------------------------------------------------

--
-- 表的结构 `help_articles`
--

CREATE TABLE `help_articles` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `description` text DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `helpful_votes` int(11) DEFAULT 0,
  `not_helpful_votes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `help_articles`
--

INSERT INTO `help_articles` (`id`, `category_id`, `title`, `slug`, `content`, `description`, `keywords`, `views`, `helpful_votes`, `not_helpful_votes`, `created_at`, `updated_at`) VALUES
(1, 1, 'How to Book a Hotel', 'how-to-book-a-hotel', 'Step-by-step guide to booking a hotel on Ered Hotel...', 'New to booking hotels online? This guide walks you through every step of reserving a room on Ered Hotel. Start by logging into your account, then use the search tool to pick your destination and travel dates. Browse available hotels, compare prices and amenities, and select your preferred room type. Add any extras like breakfast or late checkout, then proceed to payment. Double-check your details—dates, guest names, and special requests—before confirming. You’ll get an instant confirmation email, ensuring your trip is set. Perfect for first-timers or anyone wanting a stress-free booking experience!', 'booking, hotel, reservation, steps', 33, 4, 4, '2025-03-28 01:37:33', '2025-04-14 02:14:32'),
(2, 1, 'Modifying Your Booking', 'modifying-your-booking', 'Instructions for modifying your existing hotel booking...', 'Need to change your hotel plans? This article shows you how to modify your booking effortlessly. Log into your Ered Hotel account and go to “Your Bookings.” Find the reservation you want to update, then click “Modify.” You can adjust your check-in dates, switch room types, or update guest details—availability permitting. Follow the prompts to review any price changes or fees, then confirm the updates. Check your email for a new confirmation. It’s that simple to keep your travel plans flexible and tailored to your needs!', 'modify, change, update, booking', 18, 0, 0, '2025-03-28 01:37:33', '2025-04-14 02:14:35'),
(3, 2, 'Payment Methods', 'payment-methods', 'Available payment methods and how to use them...', 'Wondering how to pay for your hotel? This guide covers all payment methods Ered Hotel accepts, so you can choose what works best for you. We support major credit cards (Visa, Mastercard, etc.), debit cards, PayPal, and bank transfers in some regions. To pay, select your hotel and room, then head to the checkout page. Pick your method, enter your details carefully—card number, expiration, and CVV for cards, or log in for PayPal—and hit “Pay Now.” If your payment fails, double-check your info or try another method. You’ll get tips here to avoid hiccups and complete your booking smoothly.', 'payment, credit card, debit card, methods', 34, 7, 5, '2025-03-28 01:37:33', '2025-03-28 03:52:44'),
(4, 2, 'Understanding Refunds', 'understanding-refunds', 'Our refund policy and how to request a refund...', 'Confused about getting a refund? This article explains Ered Hotel’s refund process in detail so you know exactly what to do. Most bookings can be canceled free up to 24 hours before check-in—check your booking terms to confirm. Log into your account, find your booking under “Your Bookings,” and select “Cancel.” If eligible, your refund starts processing automatically, hitting your account in 5-10 business days depending on your bank. For non-refundable bookings or late cancellations, we’ll show you how to contact support for options. Stay informed and get your money back when it counts!', 'refund, money back, cancellation', 3, 0, 0, '2025-03-28 01:37:33', '2025-03-28 05:38:21'),
(5, 3, 'Managing Your Account', 'managing-your-account', 'How to manage your Ered Hotel account settings...', 'Want to master your Ered Hotel account? This guide teaches you how to manage everything in one place. Log in, then head to “Account Settings.” Update your email or phone number under “Profile,” tweak notification preferences to stay in the loop, and add a payment method for faster bookings. Check “Booking History” to review past trips or download receipts. You can even set travel preferences—like room type or location—to personalize your experience. Follow these steps to keep your account organized and ready for your next adventure!', 'account, settings, profile, manage', 3, 0, 0, '2025-03-28 01:37:33', '2025-03-28 05:38:29'),
(6, 3, 'Password Reset', 'password-reset', 'How to reset your password if you forgot it...', 'Locked out of your account? Don’t worry—this article walks you through resetting your Ered Hotel password in minutes. On the login page, click “Forgot Password?” Enter the email tied to your account, then check your inbox (and spam folder) for a reset link. Click it, type a new password—make it strong with letters, numbers, and symbols—then confirm. Log in with your new credentials. If the email doesn’t arrive, wait a few minutes or request another link. Get back into your account fast with these easy steps!', 'password, reset, forgot, security', 3, 0, 0, '2025-03-28 01:37:33', '2025-03-28 03:52:44'),
(7, 4, 'Common Booking Issues', 'common-booking-issues', 'Solutions to common booking problems...', 'Booking not going as planned? This guide helps you fix common issues like a flash. If your hotel isn’t showing up, check your dates or filters—try broadening your search. Payment declined? Verify your card details or switch methods. For duplicate bookings, log into “Your Bookings” to spot extras, then cancel unneeded ones if within policy. Confirmation missing? Look in spam or refresh your account. These practical solutions get you back on track, with support contact info if you’re still stuck!', 'problems, issues, fix, solution', 3, 0, 0, '2025-03-28 01:37:33', '2025-03-28 03:52:44'),
(8, 4, 'Contact Support', 'contact-support', 'How to contact our support team...', 'Need assistance? This article shows you how to reach Ered Hotel’s support team quickly and effectively. For instant help, use live chat—click “Chat” on our site, share your issue, and connect with an agent. Prefer email? Send details to support@hotelhive.com, including your booking ID if applicable, and expect a reply within 24 hours. For urgent matters, call +1 (555) 123-4567—available 24/7. Have your account or booking info ready to speed things up. Whatever your question, we’re here to solve it fast!', 'support, contact, help, assistance', 0, 0, 0, '2025-03-28 01:37:33', '2025-03-28 03:52:44'),
(9, 4, 'Website Loading Issues', 'website-loading-issues', 'If you\'re experiencing issues with our website loading:\r\n\r\n1. Clear your browser cache and cookies\r\n2. Try using a different browser\r\n3. Check your internet connection\r\n4. Disable browser extensions\r\n5. If the problem persists, try using incognito/private mode\r\n\r\nIf none of these solutions work, please contact our support team.', 'Website not loading? Fix it with this step-by-step guide. First, clear your browser cache—go to settings, find “Clear Browsing Data,” and wipe it. Still slow? Switch browsers (try Chrome or Firefox) or check your internet—restart your router if needed. Disable extensions like ad blockers that might interfere, then reload. If it’s still lagging, open an incognito window to test. These tricks usually work, but if not, reach out to support with your device details for a quick resolution!', 'loading, slow, website, performance, browser, cache', 0, 0, 0, '2025-03-28 01:37:33', '2025-03-28 03:52:44'),
(10, 4, 'Payment Processing Errors', 'payment-processing-errors', 'Common payment processing errors and their solutions:\r\n\r\n1. Card Declined\r\n- Verify card details are correct\r\n- Ensure sufficient funds\r\n- Check if your card allows online transactions\r\n- Contact your bank for authorization\r\n\r\n2. Payment Page Errors\r\n- Clear browser cache\r\n- Try a different browser\r\n- Check internet connection\r\n- Ensure you\'re using a supported payment method\r\n\r\n3. Double Charge Issues\r\n- Check your bank statement\r\n- Look for confirmation email\r\n- Contact our support with transaction details\r\n\r\nFor immediate assistance, contact our support team.', 'Payment failing? This article helps you troubleshoot common errors step-by-step. If your card’s declined, check the number, expiration, and CVV—re-enter if off. Ensure funds are available or call your bank to approve online use. For page errors, clear your browser cache (settings > clear data) or try another browser. Double-charged? Log into “Your Bookings” to confirm, then email support with your transaction ID. Follow these fixes to get your payment through and your hotel booked without delay!', 'payment error, declined, transaction failed, double charge', 4, 0, 0, '2025-03-28 01:37:33', '2025-03-28 03:52:44'),
(11, 4, 'Booking Confirmation Issues', 'booking-confirmation-issues', 'If you\'re having trouble with booking confirmations:\r\n\r\n1. Missing Confirmation Email\r\n- Check spam/junk folder\r\n- Verify email address is correct\r\n- Wait 15-30 minutes for delivery\r\n- Check booking history in your account\r\n\r\n2. Incorrect Booking Details\r\n- Screenshot the confirmation page\r\n- Note your booking reference\r\n- Contact support immediately\r\n\r\n3. Multiple Bookings\r\n- Check your account for duplicates\r\n- Review email confirmations\r\n- Contact support for assistance\r\n\r\nWe\'re here to help resolve any booking confirmation issues.', 'No confirmation email? Solve it with this guide. First, check your spam/junk folder—emails sometimes hide there. Log into “Your Bookings” to see if it’s listed; if not, verify your email address in “Account Settings.” Wait 15-30 minutes, as delays happen, then refresh. Got wrong details? Screenshot it and note your booking ID for support. For duplicates, cancel extras if refundable. These steps ensure you’re confirmed and ready to travel—support’s here if you need more help!', 'confirmation, email, booking reference, duplicate booking', 1, 0, 0, '2025-03-28 01:37:33', '2025-03-28 03:52:44'),
(12, 4, 'Mobile App Troubleshooting', 'mobile-app-troubleshooting', 'Common mobile app issues and solutions:\r\n\r\n1. App Crashes\r\n- Update to latest version\r\n- Clear app cache\r\n- Restart your device\r\n- Reinstall the app\r\n\r\n2. Login Problems\r\n- Reset password\r\n- Clear app data\r\n- Check internet connection\r\n- Update app to latest version\r\n\r\n3. Booking Features Not Working\r\n- Check app permissions\r\n- Ensure location services are enabled\r\n- Update payment information\r\n- Clear app cache\r\n\r\nFor persistent issues, please contact our support team.', 'App crashing or buggy? Fix it fast with this guide. If it crashes, update the app in your store, then clear cache—go to phone settings, apps, Ered Hotel, and clear. Restart your device next. Login issues? Reset your password via “Forgot Password” and check your signal. For booking glitches, ensure permissions (location, storage) are on and payment info’s current. Reinstall if needed—delete, redownload, and log in. These steps get your app running smoothly again!', 'app, mobile, crash, login, features', 4, 0, 0, '2025-03-28 01:37:33', '2025-03-28 05:37:46'),
(13, 4, 'Account Access Problems', 'account-access-problems', 'If you\'re having trouble accessing your account:\r\n\r\n1. Login Issues\r\n- Reset your password\r\n- Clear browser cache\r\n- Check caps lock\r\n- Verify email address\r\n\r\n2. Account Locked\r\n- Wait 30 minutes\r\n- Use password reset\r\n- Contact support for immediate unlock\r\n\r\n3. Two-Factor Authentication Issues\r\n- Check phone number is correct\r\n- Verify time zone settings\r\n- Use backup codes\r\n- Contact support for assistance\r\n\r\nWe take security seriously and are here to help.', 'Can’t get into your account? This guide has you covered. For login fails, reset your password—click “Forgot Password” on the login page, follow the email link, and set a new one. Clear cache if errors persist (browser settings > clear data). Account locked? Wait 30 minutes or contact support for an unlock. Two-factor trouble? Verify your phone number’s right, check time settings, or use backup codes. These steps restore access fast—reach out if you’re still locked out!', 'login, access, locked, security, two-factor', 2, 0, 0, '2025-03-28 01:37:33', '2025-03-28 05:37:32');

-- --------------------------------------------------------

--
-- 表的结构 `help_categories`
--

CREATE TABLE `help_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `help_categories`
--

INSERT INTO `help_categories` (`id`, `name`, `slug`, `icon`, `description`, `created_at`) VALUES
(1, 'Booking Help', 'booking-help', 'fa-calendar-check', 'Get assistance with hotel bookings, reservations, and modifications', '2025-03-28 01:37:33'),
(2, 'Payment Help', 'payment-help', 'fa-credit-card', 'Learn about payment methods, refunds, and billing', '2025-03-28 01:37:33'),
(3, 'Account Help', 'account-help', 'fa-user-circle', 'Manage your account settings and preferences', '2025-03-28 01:37:33'),
(4, 'Troubleshooting', 'troubleshooting', 'fa-wrench', 'Common issues and their solutions', '2025-03-28 01:37:33');

-- --------------------------------------------------------

--
-- 表的结构 `hotels`
--

CREATE TABLE `hotels` (
  `hotel_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `city` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `hotels`
--

INSERT INTO `hotels` (`hotel_id`, `name`, `location`, `city`, `country`, `description`, `image_url`, `created_at`) VALUES
(1, 'Ered Hotel KL Central', 'Kuala Lumpur City Center', 'Kuala Lumpur', 'Malaysia', 'Modern luxury hotel in the heart of Kuala Lumpur with stunning city views and world-class amenities.', 'images/hotel/kl_central.jpg', '2025-03-24 00:53:43'),
(2, 'Ered Hotel Twin Towers', 'KLCC District', 'Kuala Lumpur', 'Malaysia', 'Premium hotel steps away from the iconic Petronas Twin Towers with elegant suites and fine dining.', 'images/hotel/twin_towers.jpg', '2025-03-24 00:53:43'),
(3, 'Ered Hotel Bukit Bintang', 'Bukit Bintang', 'Kuala Lumpur', 'Malaysia', 'Trendy hotel in the entertainment district with rooftop pool and vibrant nightlife access.', 'images/hotel/bukit_bintang.jpg', '2025-03-24 01:29:25');

-- --------------------------------------------------------

--
-- 表的结构 `hotel_facilities`
--

CREATE TABLE `hotel_facilities` (
  `f_id` int(11) NOT NULL,
  `h_id` int(11) NOT NULL,
  `facility` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `hotel_facilities`
--

INSERT INTO `hotel_facilities` (`f_id`, `h_id`, `facility`) VALUES
(1, 1, 'Infinity Pool'),
(2, 1, 'Spa Center'),
(3, 1, 'Fine Dining Restaurant'),
(4, 1, 'Private Beach Access'),
(5, 1, 'Helipad'),
(6, 1, '24/7 Butler Service'),
(7, 1, 'Luxury Spa'),
(8, 1, 'Private Balcony'),
(9, 1, 'Ocean View'),
(10, 1, 'Premium Wi-Fi'),
(11, 2, 'Beachfront Access'),
(12, 2, 'Water Sports Center'),
(13, 2, 'Beach Bar'),
(14, 2, 'Sunset Deck'),
(15, 2, 'Beach Umbrellas'),
(16, 2, 'Snorkeling Equipment'),
(17, 2, 'Beach Volleyball Court'),
(18, 2, 'Beach Massage Service'),
(19, 2, 'Beach Restaurant'),
(20, 2, 'Beach Activities Desk'),
(21, 3, 'Business Center'),
(22, 3, 'Conference Rooms'),
(23, 3, 'Fitness Center'),
(24, 3, 'Rooftop Restaurant'),
(25, 3, 'City View Rooms'),
(26, 3, 'Express Check-in'),
(27, 3, 'Laundry Service'),
(28, 3, 'Room Service'),
(29, 3, 'High-Speed Internet'),
(30, 3, 'Luggage Storage');

-- --------------------------------------------------------

--
-- 表的结构 `hotel_img`
--

CREATE TABLE `hotel_img` (
  `hi_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `hotel_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `hotel_img`
--

INSERT INTO `hotel_img` (`hi_id`, `hotel_id`, `hotel_image`, `created_at`) VALUES
(1, 1, 'images/hotel/kl_central_1.jpg', '2025-03-24 00:53:43'),
(2, 1, 'images/hotel/kl_central_2.jpg', '2025-03-24 00:53:43'),
(3, 1, 'images/hotel/kl_central_3.jpg', '2025-03-24 00:53:43'),
(4, 1, 'images/hotel/kl_central_4.jpg', '2025-03-24 00:53:43'),
(5, 1, 'images/hotel/kl_central_5.jpg', '2025-03-24 00:53:43'),
(6, 2, 'images/hotel/twin_towers_1.jpg', '2025-03-24 00:53:43'),
(7, 2, 'images/hotel/twin_towers_2.jpg', '2025-03-24 00:53:43'),
(8, 2, 'images/hotel/twin_towers_3.jpg', '2025-03-24 00:53:43'),
(9, 2, 'images/hotel/twin_towers_4.jpg', '2025-03-24 00:53:43'),
(10, 2, 'images/hotel/twin_towers_5.jpg', '2025-03-24 00:53:43'),
(11, 3, 'images/hotel/bukit_bintang_1.jpg', '2025-03-24 01:29:25'),
(12, 3, 'images/hotel/bukit_bintang_2.jpg', '2025-03-24 01:29:25'),
(13, 3, 'images/hotel/bukit_bintang_3.jpg', '2025-03-24 01:29:25'),
(14, 3, 'images/hotel/bukit_bintang_4.jpg', '2025-03-24 01:29:25'),
(15, 3, 'images/hotel/bukit_bintang_5.jpg', '2025-03-24 01:29:25');

-- --------------------------------------------------------

--
-- 表的结构 `hotel_managers`
--

CREATE TABLE `hotel_managers` (
  `manager_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `manager_name` varchar(100) NOT NULL,
  `manager_password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `hotel_managers`
--

INSERT INTO `hotel_managers` (`manager_id`, `hotel_id`, `manager_name`, `manager_password`, `email`, `phone`, `created_at`) VALUES
(1, 1, 'Derick', '$2y$10$s14hWO075EjBaaONb0FMCuHzLKyFPkt5RabBiU1akx.xYnXBdyGia', 'derick@gmail.com', '0123456789', '2025-04-08 14:12:41'),
(2, 2, 'Sarah', '$2y$10$s14hWO075EjBaaONb0FMCuHzLKyFPkt5RabBiU1akx.xYnXBdyGia', 'sarah@gmail.com', '0123456790', '2025-04-08 14:12:41');

-- --------------------------------------------------------


-- --------------------------------------------------------

--
-- 表的结构 `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `hotel_id`, `rating`, `comment`, `created_at`) VALUES
(3, 5, 1, 5, 'This hotel rooms is awesome! Recommend to book a room from this hotel.', '2025-03-30 09:13:22'),
(4, 5, 1, 3, 'Need improvement', '2025-04-03 03:24:02'),
(5, 5, 2, 2, 'Need to have improvement.', '2025-04-04 00:16:56'),
(6, 5, 3, 5, 'Satisfied', '2025-04-14 02:10:36');

-- --------------------------------------------------------

--
-- 表的结构 `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `max_guests` int(11) NOT NULL,
  `bed_type` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `availability` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `rooms`
--

INSERT INTO `rooms` (`room_id`, `hotel_id`, `room_type`, `price_per_night`, `max_guests`, `bed_type`, `image_url`, `availability`, `created_at`) VALUES
(1, 1, 'KL Central Deluxe Suite', 385.00, 2, 'King', 'images/room/kl_central_suite.jpg', 1, '2025-03-24 00:53:43'),
(2, 1, 'KL Central Executive Villa', 585.00, 4, 'King + Queen', 'images/room/kl_central_villa.jpg', 1, '2025-03-24 00:53:43'),
(3, 2, 'Twin Towers Premium Room', 165.00, 2, 'Queen', 'images/room/twin_towers_room.jpg', 1, '2025-03-24 00:53:43'),
(4, 2, 'Twin Towers Twin Room', 195.00, 2, 'Twin', 'images/room/twin_towers_twin.jpg', 1, '2025-03-24 01:29:25'),
(5, 3, 'Bukit Bintang Suite', 495.00, 2, 'King', 'images/room/bukit_bintang_suite.jpg', 1, '2025-03-24 01:29:25');

-- --------------------------------------------------------

--
-- 表的结构 `room_amenities`
--

CREATE TABLE `room_amenities` (
  `a_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `amenities` varchar(255) NOT NULL,
  `room_size` varchar(50) NOT NULL,
  `beds` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `room_amenities`
--

INSERT INTO `room_amenities` (`a_id`, `room_id`, `amenities`, `room_size`, `beds`) VALUES
(1, 1, 'Free WiFi, Air Conditioning, TV, Mini Bar, Safe', '28 m²', '1 king bed'),
(2, 1, 'Desk, Phone, Hairdryer, Iron, Coffee Maker', '28 m²', '1 king bed'),
(3, 1, 'Balcony, Ocean View, Sitting Area', '28 m²', '1 king bed'),
(4, 2, 'Free WiFi, Air Conditioning, TV, Mini Bar, Safe', '45 m²', '2 queen beds'),
(5, 2, 'Desk, Phone, Hairdryer, Iron, Coffee Maker', '45 m²', '2 queen beds'),
(6, 2, 'Balcony, Ocean View, Sitting Area, Jacuzzi', '45 m²', '2 queen beds'),
(7, 2, 'Kitchen, Dining Area, Living Room', '45 m²', '2 queen beds'),
(8, 3, 'Free WiFi, Air Conditioning, TV, Mini Bar, Safe', '22 m²', '2 twin beds'),
(9, 3, 'Desk, Phone, Hairdryer, Iron, Coffee Maker', '22 m²', '2 twin beds'),
(10, 4, 'Free WiFi, Air Conditioning, TV, Mini Bar, Safe', '50 m²', '1 king bed and 1 sofa bed'),
(11, 4, 'Desk, Phone, Hairdryer, Iron, Coffee Maker', '50 m²', '1 king bed and 1 sofa bed'),
(12, 4, 'Balcony, Ocean View, Sitting Area, Jacuzzi', '50 m²', '1 king bed and 1 sofa bed'),
(13, 4, 'Kitchen, Dining Area, Living Room', '50 m²', '1 king bed and 1 sofa bed'),
(14, 5, 'Free WiFi, Air Conditioning, TV, Mini Bar, Safe', '30 m²', '1 king bed'),
(15, 5, 'Desk, Phone, Hairdryer, Iron, Coffee Maker', '30 m²', '1 king bed'),
(16, 5, 'Balcony, Ocean View, Sitting Area', '30 m²', '1 king bed');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `c_status` enum('active','banned') NOT NULL DEFAULT 'active',
  `user_type` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `profile_img`, `c_status`, `user_type`, `created_at`) VALUES
(5, 'UnknownUser', 'unknown@gmail.com', '$2y$10$takFQOic/rOzAXl760UAi.cPAMwCTRuMhWsm563RabxKlNGmxT1VG', 'Unknown', 'User', '0123949281', 'images/profile/1.jpg', 'active', 'customer', '2025-03-25 13:56:36');

--
-- 转储表的索引
--

--
-- 表的索引 `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `admin_name` (`admin_name`);

--
-- 表的索引 `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);


--
-- 表的索引 `country_img`
--
ALTER TABLE `country_img`
  ADD PRIMARY KEY (`ci_id`),
  ADD KEY `hotel_id` (`hotel_id`);



--
-- 表的索引 `help_articles`
--
ALTER TABLE `help_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- 表的索引 `help_categories`
--
ALTER TABLE `help_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- 表的索引 `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`hotel_id`);

--
-- 表的索引 `hotel_facilities`
--
ALTER TABLE `hotel_facilities`
  ADD PRIMARY KEY (`f_id`),
  ADD KEY `h_id` (`h_id`);

--
-- 表的索引 `hotel_img`
--
ALTER TABLE `hotel_img`
  ADD PRIMARY KEY (`hi_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- 表的索引 `hotel_managers`
--
ALTER TABLE `hotel_managers`
  ADD PRIMARY KEY (`manager_id`),
  ADD UNIQUE KEY `hotel_id` (`hotel_id`);


--
-- 表的索引 `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- 表的索引 `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- 表的索引 `room_amenities`
--
ALTER TABLE `room_amenities`
  ADD PRIMARY KEY (`a_id`),
  ADD KEY `room_id` (`room_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;


--
-- 使用表AUTO_INCREMENT `country_img`
--
ALTER TABLE `country_img`
  MODIFY `ci_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;



--
-- 使用表AUTO_INCREMENT `help_articles`
--
ALTER TABLE `help_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `help_categories`
--
ALTER TABLE `help_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `hotels`
--
ALTER TABLE `hotels`
  MODIFY `hotel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `hotel_facilities`
--
ALTER TABLE `hotel_facilities`
  MODIFY `f_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- 使用表AUTO_INCREMENT `hotel_img`
--
ALTER TABLE `hotel_img`
  MODIFY `hi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `hotel_managers`
--
ALTER TABLE `hotel_managers`
  MODIFY `manager_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;


--
-- 使用表AUTO_INCREMENT `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `room_amenities`
--
ALTER TABLE `room_amenities`
  MODIFY `a_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 限制导出的表
--

--
-- 限制表 `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;




--
-- 限制表 `help_articles`
--
ALTER TABLE `help_articles`
  ADD CONSTRAINT `help_articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `help_categories` (`id`) ON DELETE CASCADE;

--
-- 限制表 `hotel_facilities`
--
ALTER TABLE `hotel_facilities`
  ADD CONSTRAINT `hotel_facilities_ibfk_1` FOREIGN KEY (`h_id`) REFERENCES `hotels` (`hotel_id`) ON DELETE CASCADE;

--
-- 限制表 `hotel_img`
--
ALTER TABLE `hotel_img`
  ADD CONSTRAINT `hotel_img_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`hotel_id`) ON DELETE CASCADE;

--
-- 限制表 `hotel_managers`
--
ALTER TABLE `hotel_managers`
  ADD CONSTRAINT `fk_hotel_manager` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`hotel_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`hotel_id`) ON DELETE CASCADE;

--
-- 限制表 `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`hotel_id`) ON DELETE CASCADE;

--
-- 限制表 `room_amenities`
--
ALTER TABLE `room_amenities`
  ADD CONSTRAINT `room_amenities_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
