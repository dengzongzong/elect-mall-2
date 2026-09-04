-- ============================================================
-- 商品分类数据导入：陶瓷贴片电容器
-- 数据结构：
-- - 一级分类：陶瓷贴片电容器 (pid = 0)
-- - 二级分类：13个品牌 (pid = 一级分类ID)
-- 
-- 此脚本会自动执行，无需手动操作
-- ============================================================

-- 删除已存在的陶瓷贴片电容器分类（先删子分类，再删父分类）
SET @pid := (SELECT id FROM eb_store_category WHERE cate_name = '陶瓷贴片电容器' AND pid = 0 LIMIT 1);
DELETE FROM eb_store_category WHERE pid = @pid;
DELETE FROM eb_store_category WHERE id = @pid;

-- 插入一级分类：陶瓷贴片电容器
INSERT INTO eb_store_category (pid, cate_name, sort, pic, is_show, add_time) 
VALUES (0, '陶瓷贴片电容器', 1, '', 1, UNIX_TIMESTAMP());

-- 获取刚插入的一级分类ID
SET @parent_id := LAST_INSERT_ID();

-- 插入13个二级分类（品牌）
INSERT INTO eb_store_category (pid, cate_name, sort, pic, is_show, add_time) VALUES
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