-- ============================================================
-- 陶瓷贴片电容器分类导入 SQL
-- 功能：删除所有现有商品分类和商品数据，只保留陶瓷贴片电容器分类
-- ============================================================

-- 设置编码
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 第一步：删除所有商品相关数据（清空）
-- ============================================================

-- 删除所有商品（如果有商品）
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

-- 重置自增ID
ALTER TABLE `eb_store_product` AUTO_INCREMENT = 1;

-- ============================================================
-- 第二步：删除所有现有分类，只保留陶瓷贴片电容器相关分类
-- （用户要求只保留我们要导入的两级分类）
-- ============================================================

-- 先删除陶瓷贴片电容器（如果已存在）及其子分类
DELETE FROM `eb_store_category` 
WHERE `cate_name` = '陶瓷贴片电容器' 
   OR `pid` IN (SELECT id FROM `eb_store_category` WHERE `cate_name` = '陶瓷贴片电容器');

-- ============================================================
-- 第三步：插入一级分类：陶瓷贴片电容器
-- ============================================================

INSERT INTO `eb_store_category` 
(`id`, `pid`, `cate_name`, `sort`, `pic`, `is_show`, `add_time`) 
VALUES 
(NULL, 0, '陶瓷贴片电容器', 1, '', 1, UNIX_TIMESTAMP());

-- 获取最后插入的ID（供下面使用）
SET @parent_id = LAST_INSERT_ID();

-- ============================================================
-- 第四步：插入二级分类（13个品牌）
-- ============================================================

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

-- ============================================================
-- 完成
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- 显示结果
SELECT '导入完成！' AS message;
SELECT id, pid, cate_name, sort FROM eb_store_category WHERE pid = @parent_id ORDER BY sort;
