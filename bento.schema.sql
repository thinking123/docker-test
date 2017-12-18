/*
 Navicat Premium Data Transfer

 Source Server         : bento
 Source Server Type    : MySQL
 Source Server Version : 50720
 Source Host           : 127.0.0.1
 Source Database       : bento_dev

 Target Server Type    : MySQL
 Target Server Version : 50720
 File Encoding         : utf-8

 Date: 12/18/2017 21:55:37 PM
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `File`
-- ----------------------------
DROP TABLE IF EXISTS `File`;
CREATE TABLE `File` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '文件名',
  `userId` int(11) unsigned DEFAULT NULL COMMENT '用户ID',
  `teamId` int(11) unsigned DEFAULT NULL COMMENT '用户组ID',
  `access` enum('0','1') NOT NULL DEFAULT '0' COMMENT '文件访问权限(0:私有1:公共)',
  `status` enum('0','1') NOT NULL DEFAULT '1' COMMENT '文件状态(0:已删除1:正常)',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updatedAt` timestamp NULL DEFAULT NULL COMMENT '最后修改时间',
  PRIMARY KEY (`id`),
  KEY `idx_userId` (`userId`),
  KEY `idx_teamId` (`teamId`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `Layer`
-- ----------------------------
DROP TABLE IF EXISTS `Layer`;
CREATE TABLE `Layer` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Layer ID',
  `name` varchar(100) NOT NULL COMMENT 'Layer 名称',
  `type` enum('1','2') DEFAULT NULL COMMENT 'Layer 类型',
  `fileId` bigint(20) unsigned NOT NULL COMMENT '所属文件ID',
  `parentId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '父亲ID',
  `position` float unsigned NOT NULL DEFAULT '0' COMMENT '位置',
  `data` text COMMENT '数据',
  `styles` text,
  `render` enum('0','1') NOT NULL DEFAULT '1' COMMENT '是否渲染',
  `status` enum('0','1') NOT NULL DEFAULT '1' COMMENT '状态(0:已删除1:正常)',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updatedAt` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `Team`
-- ----------------------------
DROP TABLE IF EXISTS `Team`;
CREATE TABLE `Team` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '组名称',
  `ownerId` int(10) unsigned NOT NULL COMMENT '所有者ID',
  `createdBy` int(10) unsigned NOT NULL COMMENT '创建者ID',
  `status` enum('0','1') NOT NULL DEFAULT '1' COMMENT '状态(0:已删除1:正常)',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updatedAt` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_ownerId` (`ownerId`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `TeamUser`
-- ----------------------------
DROP TABLE IF EXISTS `TeamUser`;
CREATE TABLE `TeamUser` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL COMMENT '用户ID',
  `teamId` int(10) unsigned NOT NULL COMMENT '组ID',
  `status` enum('0','1') DEFAULT '1' COMMENT '状态(0:已解除;1-存续中)',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updatedAt` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_userId` (`userId`),
  KEY `idx_teamId` (`teamId`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `Token`
-- ----------------------------
DROP TABLE IF EXISTS `Token`;
CREATE TABLE `Token` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `userId` int(11) unsigned NOT NULL COMMENT '用户ID',
  `agent` varchar(250) DEFAULT NULL COMMENT '客户端 Agent',
  `ip` varchar(40) NOT NULL COMMENT 'IP 地址',
  `accessToken` varchar(40) DEFAULT NULL COMMENT 'access token',
  `refreshToken` varchar(40) NOT NULL COMMENT 'refresh token',
  `accessTokenExpiredAt` timestamp NULL DEFAULT NULL,
  `refreshTokenExpiredAt` timestamp NULL DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updatedAt` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `status` enum('0','1') NOT NULL DEFAULT '1' COMMENT 'Token 状态(0:已删除, 1:正常)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_refreshToken` (`refreshToken`) USING HASH,
  UNIQUE KEY `uniq_accessToken` (`accessToken`) USING HASH,
  KEY `idx_userId` (`userId`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `User`
-- ----------------------------
DROP TABLE IF EXISTS `User`;
CREATE TABLE `User` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `name` varchar(120) DEFAULT NULL COMMENT '用户名称',
  `givenName` varchar(60) DEFAULT NULL COMMENT '用户的名',
  `familyName` varchar(60) DEFAULT NULL COMMENT '用户的姓',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像地址',
  `email` varchar(255) DEFAULT NULL COMMENT '电子邮件地址',
  `googleId` varchar(21) DEFAULT NULL COMMENT 'Google ID',
  `salt` varchar(40) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_googleId` (`googleId`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
