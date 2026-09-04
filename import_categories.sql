-- ============================================================
-- 陶瓷贴片电容器分类导入 SQL
-- 功能：删除所有现有商品数据，重新插入两级分类
-- 修复：解决子查询引用同一表的错误问题
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ========== 第一步：删除所有商品相关数据 ==========

DELETE FROM `eb_store_product`;
DELETE FROM `eb_store_product_cate`;
DELETE FROM `eb_store_product_attr`;
DELETE FROM `eb_store_product_attr_value`;
DELETE FROM `eb_store_product_attr_result`;
DELETE FROM `eb_store_product_description`;
DELETE FROM `eb_store_product_coupon`;
DELETE FROM `eb_store_product_label`;
DELETE FROM `eb_store_product_log`;
DELETE FROM `eb_store_product_param`;
DELETE FROM `eb_store_product_protection`;
DELETE FROM `eb_store_product_relation`;
DELETE FROM `eb_store_product_reply`;
DELETE FROM `eb_store_product_virtual`;
DELETE FROM `eb_store_visit`;

ALTER TABLE `eb_store_product` AUTO_INCREMENT = 1;

-- ========== 第二步：删除陶瓷贴片电容器分类（使用变量避免子查询同一表错误） ==========

-- 先把要删除的ID存到变量
SELECT id INTO @tmp_pid FROM `eb_store_category` WHERE `cate_name` = '陶瓷贴片电容器' AND `pid` = 0 LIMIT 1;

-- 删除子分类
DELETE FROM `eb_store_category` WHERE `pid` = @tmp_pid;

-- 删除一级分类
DELETE FROM `eb_store_category` WHERE `id` = @tmp_pid;

-- ========== 第三步：插入一级分类：陶瓷贴片电容器 ==========

INSERT INTO `eb_store_category` 
(`pid`, `cate_name`, `sort`, `pic`, `is_show`, `add_time`) 
VALUES 
(0, '陶瓷贴片电容器', 1, '', 1, UNIX_TIMESTAMP());

SET @parent_id = LAST_INSERT_ID();

-- ========== 第四步：插入13个二级品牌分类 ==========

INSERT INTO `eb_store_category` 
(`pid`, `cate_name`, `sort`, `pic`, `is_show`, `add_time`) 
VALUES 
(@parent_id, 'muRata(村田)', 1, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'TDK', 2, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'Taiyo Yuden(太诱)', 3, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'Kyocera(京瓷)', 4, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'Walsin(华科)', 5, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'SAMSUNG(三星)', 6, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'Holy Stone(禾伸堂)', 7, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'PSA(信昌)', 8, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'Yageo(国巨)', 9, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'FH(风华)', 10, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'CCTC(三环)', 11, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'VIYONG(微容)', 12, '', 1, UNIX_TIMESTAMP()),
(@parent_id, 'SAMWHA(三和)', 13, '', 1, UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;

-- 查看结果
SELECT '导入完成！' AS message;
SELECT id, pid, cate_name, sort FROM eb_store_category WHERE pid = @parent_id ORDER BY sort;