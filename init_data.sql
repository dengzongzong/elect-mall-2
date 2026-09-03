-- ============================================================
-- 数据库初始化数据脚本
-- 包含：品牌表创建+数据、新闻数据、PC首页广告位配置
-- 适用于 CRMEB-BZ 电商系统
-- ============================================================

-- 创建品牌表
CREATE TABLE IF NOT EXISTS `eb_brand` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name_cn` varchar(255) NOT NULL DEFAULT '' COMMENT '中文名',
    `name_en` varchar(255) NOT NULL DEFAULT '' COMMENT '英文名',
    `initial` varchar(10) NOT NULL DEFAULT '' COMMENT '首字母',
    `logo` varchar(255) NOT NULL DEFAULT '' COMMENT '品牌logo',
    `product_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '产品数量',
    `sort` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '排序',
    `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态 1=显示 0=隐藏',
    `is_authorized` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否授权代理',
    `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_del` tinyint(1) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_initial` (`initial`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='品牌表';

-- 插入品牌数据
INSERT INTO `eb_brand` (`name_en`, `name_cn`, `initial`, `product_count`, `is_authorized`, `sort`) VALUES
('Amphenol', '安费诺', 'A', 10969, 0, 1),
('ABLIC(SII)', '精工', 'A', 3235, 0, 2),
('ALPS', '阿尔卑斯', 'A', 1825, 0, 3),
('AOTE', '奥特', 'A', 608, 0, 4),
('AOS', '美国万代', 'A', 1998, 0, 5),
('Allegro', '急速微', 'A', 260, 0, 6),
('AISHI', '艾华', 'A', 1524, 0, 7),
('AVX', 'AVX', 'A', 5921, 0, 8),
('Brightking', '君耀电子', 'B', 6785, 0, 9),
('BORN', '伯恩', 'B', 937, 0, 10),
('Broadcom', '博通', 'B', 224, 0, 11),
('Bridgelux', '普瑞光电', 'B', 4398, 0, 12),
('CCTC', '三环', 'C', 6086, 0, 13),
('CHILISIN', '奇力新', 'C', 5667, 0, 14),
('CJTconn', '长江连接器', 'C', 4166, 0, 15),
('CRMICRO', '华润微', 'C', 612, 0, 16),
('Diodes', '达尔', 'D', 10322, 0, 17),
('DARFON', '达方', 'D', 1773, 0, 18),
('EIC', 'EIC', 'E', 9224, 0, 19),
('Everlight', '亿光', 'E', 3083, 0, 20),
('EVER OHMS', '天二', 'E', 1674, 0, 21),
('Epson', '爱普生', 'E', 391, 0, 22),
('FH', '风华', 'F', 32235, 1, 23),
('FOJAN', '富捷', 'F', 14073, 0, 24),
('Faratronic', '法拉', 'F', 2298, 0, 25),
('FCom', '富士水晶', 'F', 31960, 0, 26),
('GigaDevice', '兆易创新', 'G', 1977, 0, 27),
('GOFORD', '谷峰', 'G', 1098, 0, 28),
('HRS', '广濑', 'H', 3875, 0, 29),
('HCI', '杭晶', 'H', 5993, 0, 30),
('HGSEMI', '华冠', 'H', 4261, 0, 31),
('Holy Stone', '禾伸堂', 'H', 5859, 0, 32),
('Infineon', '英飞凌', 'I', 5544, 1, 33),
('ISND', '华信安', 'I', 5923, 0, 34),
('Inventchip', '瞻芯电子', 'I', 239, 0, 35),
('JST', 'JST', 'J', 8698, 1, 36),
('JSMSEMI', '杰盛微', 'J', 6237, 0, 37),
('JSCJ', '长晶科技', 'J', 3082, 0, 38),
('JINGDAO', '晶导微', 'J', 3559, 0, 39),
('KEMET', '基美', 'K', 44506, 1, 40),
('KOA Speer', '日本兴亚', 'K', 3337, 0, 41),
('KEFA', '科发', 'K', 44410, 0, 42),
('Kyocera', '京瓷', 'K', 1613, 0, 43),
('LIZ', '丽智', 'L', 12681, 0, 44),
('LRC', '乐山无线电', 'L', 10090, 0, 45),
('Lite-On', '光宝', 'L', 2271, 0, 46),
('Littelfuse', '美国力特', 'L', 3874, 0, 47),
('Murata', '村田', 'M', 15642, 1, 48),
('MOLEX', '莫仕', 'M', 6701, 1, 49),
('MCC', '美微科', 'M', 2581, 0, 50),
('MPS', '芯源系统', 'M', 2578, 0, 51),
('Microchip', '微芯', 'M', 1264, 0, 52),
('Nexperia', '安世', 'N', 19952, 1, 53),
('NXP', '恩智浦', 'N', 1195, 1, 54),
('NCC', '贵弥功', 'N', 1154, 0, 55),
('Nichicon', '尼吉康', 'N', 2133, 0, 56),
('NCE', '新洁能', 'N', 1361, 0, 57),
('ON', '安森美', 'O', 10982, 1, 58),
('OSRAM', '欧司朗', 'O', 3359, 0, 59),
('Panasonic', '松下', 'P', 30972, 1, 60),
('PASTERNACK', 'PASTERNACK', 'P', 41055, 0, 61),
('Panjit', '强茂', 'P', 5026, 0, 62),
('Pulse', '普思', 'P', 1201, 0, 63),
('ROHM', '罗姆', 'R', 58163, 1, 64),
('RALEC', '旺诠', 'R', 12487, 0, 65),
('Renesas', '瑞萨', 'R', 761, 1, 66),
('Richtek', '立锜', 'R', 2827, 0, 67),
('Rubycon', '红宝石', 'R', 1808, 0, 68),
('Sunlord', '顺络', 'S', 7188, 0, 69),
('SUNMATE', '森美特', 'S', 12246, 0, 70),
('SAMSUNG', '三星', 'S', 6966, 0, 71),
('ST', '意法半导体', 'S', 8391, 1, 72),
('SCTF', '星通时频', 'S', 4126, 0, 73),
('SILERGY', '矽力杰', 'S', 1856, 0, 74),
('SHARP', '夏普', 'S', 193, 0, 75),
('SILAN', '士兰微', 'S', 461, 0, 76),
('TDK', 'TDK', 'T', 80013, 1, 77),
('TE', '泰科', 'T', 19997, 1, 78),
('TA-I', '大毅', 'T', 18525, 0, 79),
('Toshiba', '东芝', 'T', 3852, 0, 80),
('Taiyo Yuden', '太诱', 'T', 12842, 0, 81),
('TI', '德州仪器', 'T', 7674, 1, 82),
('Torex', '特瑞仕', 'T', 51253, 0, 83),
('Uni-Ohm', '厚声', 'U', 15427, 0, 84),
('UMW', '友台', 'U', 4387, 0, 85),
('Vishay', '威世', 'V', 59899, 1, 86),
('VIKING', '光颉', 'V', 2903, 0, 87),
('VIIYONG', '微容', 'V', 2674, 0, 88),
('Walsin', '华科', 'W', 30446, 0, 89),
('WORLDPO', '沃德披欧', 'W', 11632, 0, 90),
('Walter', '华德', 'W', 1569, 0, 91),
('WINSOK', '微硕', 'W', 928, 0, 92),
('WAY-ON', '维安', 'W', 2291, 0, 93),
('XKB Connection', '星坤', 'X', 15567, 0, 94),
('XR', '祥如', 'X', 6704, 0, 95),
('XYECONN', '辛译', 'X', 4972, 0, 96),
('XFCN', '兴飞', 'X', 1570, 0, 97),
('Yageo', '国巨', 'Y', 93178, 1, 98),
('YXC', '扬兴', 'Y', 4499, 0, 99),
('YFW', '佑风微', 'Y', 4117, 0, 100),
('Yangjie', '扬杰', 'Y', 1327, 0, 101),
('ZG', '中鑫半导体', 'Z', 7549, 0, 102),
('3PEAK', '思瑞浦', '#', 692, 0, 103)
ON DUPLICATE KEY UPDATE `name_cn` = VALUES(`name_cn`);

-- ============================================================
-- 新闻数据
-- ============================================================

-- 创建新闻分类
INSERT INTO `eb_article_category` (`id`, `pid`, `title`, `intr`, `image`, `status`, `sort`, `is_del`, `add_time`, `hidden`)
VALUES (1, 0, '新闻动态', '电子元器件行业新闻动态', '', 1, 0, 0, '2026/09/03', 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 插入新闻数据（15条）
INSERT INTO `eb_article` (`id`, `cid`, `title`, `author`, `image_input`, `synopsis`, `share_title`, `share_synopsis`, `visit`, `sort`, `url`, `status`, `add_time`, `hide`, `admin_id`, `mer_id`, `product_id`, `is_hot`, `is_banner`) VALUES
(1, 1, '2026年唯样商城积分清零通知', '唯样商城', '', '关于2026年唯样商城积分清零的公告说明', '2026年唯样商城积分清零通知', '关于2026年唯样商城积分清零的公告说明', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(2, 1, '唯样商城关于承接JST-Purple平台服务的公告', '唯样商城', '', '唯样商城正式承接JST-Purple平台服务的公告', '唯样商城关于承接JST-Purple平台服务的公告', '唯样商城正式承接JST-Purple平台服务的公告', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(3, 1, '关于近期警惕虚假短信的声明', '唯样商城', '', '关于近期警惕虚假短信的重要声明', '关于近期警惕虚假短信的声明', '关于近期警惕虚假短信的重要声明', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(4, 1, '唯样商城ONEYAC-官方简介-2026版', '唯样商城', '', '唯样商城ONEYAC官方简介2026版', '唯样商城ONEYAC-官方简介-2026版', '唯样商城ONEYAC官方简介2026版', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(5, 1, '以1005尺寸实现0.33W！ROHM开发出高抗浪涌贴片电阻器', '唯样商城', '', 'ROHM开发出高抗浪涌贴片电阻器，以1005尺寸实现0.33W功率', '以1005尺寸实现0.33W！ROHM开发出高抗浪涌贴片电阻器', 'ROHM开发出高抗浪涌贴片电阻器，以1005尺寸实现0.33W功率', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(6, 1, '一颗不到1分钱的MLCC，为什么掌控着全球AI产业命脉？', '唯样商城', '', 'MLCC（多层陶瓷电容器）作为电子元器件中的重要组成部分，正在掌控全球AI产业命脉', '一颗不到1分钱的MLCC，为什么掌控着全球AI产业命脉？', 'MLCC（多层陶瓷电容器）作为电子元器件中的重要组成部分，正在掌控全球AI产业命脉', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(7, 1, 'YXC | 高精度时间管理的完美搭档：YSN8563 RTC与32.768kHz晶振的协同设计', '唯样商城', '', 'YXC推出YSN8563 RTC与32.768kHz晶振协同设计方案', 'YXC | 高精度时间管理的完美搭档：YSN8563 RTC与32.768kHz晶振的协同设计', 'YXC推出YSN8563 RTC与32.768kHz晶振协同设计方案', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(8, 1, 'TE | Euro NCAP 2026 来了，您的座椅位置检测还够用吗？', '唯样商城', '', 'TE Connectivity为您解读Euro NCAP 2026对座椅位置检测的新要求', 'TE | Euro NCAP 2026 来了，您的座椅位置检测还够用吗？', 'TE Connectivity为您解读Euro NCAP 2026对座椅位置检测的新要求', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(9, 1, '毫欧科普：合金电阻在精密设备中的应用剖析与选型要点', '唯样商城', '', '详解合金电阻在精密设备中的应用及选型要点', '毫欧科普：合金电阻在精密设备中的应用剖析与选型要点', '详解合金电阻在精密设备中的应用及选型要点', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(10, 1, '瞻芯第三代 SiC MOSFET 以优异性能和可靠性，支撑千万颗级交付规模', '唯样商城', '', '瞻芯第三代SiC MOSFET以优异性能和可靠性支撑千万颗级交付规模', '瞻芯第三代 SiC MOSFET 以优异性能和可靠性，支撑千万颗级交付规模', '瞻芯第三代SiC MOSFET以优异性能和可靠性支撑千万颗级交付规模', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(11, 1, 'Nexperia与Aurobay Technologies探讨下一代电力半导体解决方案', '唯样商城', '', 'Nexperia与Aurobay Technologies探讨下一代电气化动力总成解决方案', 'Nexperia与Aurobay Technologies探讨下一代电力半导体解决方案', 'Nexperia与Aurobay Technologies探讨下一代电气化动力总成解决方案', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(12, 1, '下一代功率半导体面临哪些挑战？MLCC应用成为关键', '唯样商城', '', '探讨下一代功率半导体的挑战及MLCC应用的关键作用', '下一代功率半导体面临哪些挑战？MLCC应用成为关键', '探讨下一代功率半导体的挑战及MLCC应用的关键作用', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(13, 1, '从多颗晶振到一颗时钟发生器芯片｜YXC-SYNK智能显示屏时钟方案', '唯样商城', '', 'YXC-SYNK智能显示屏时钟方案，从多颗晶振到一颗时钟发生器芯片', '从多颗晶振到一颗时钟发生器芯片｜YXC-SYNK智能显示屏时钟方案', 'YXC-SYNK智能显示屏时钟方案，从多颗晶振到一颗时钟发生器芯片', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(14, 1, 'AOTE | RS422与RS485接口怎么选？读懂差异，选对收发芯片', '唯样商城', '', '详解RS422与RS485接口的差异及收发芯片选型指南', 'AOTE | RS422与RS485接口怎么选？读懂差异，选对收发芯片', '详解RS422与RS485接口的差异及收发芯片选型指南', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0),
(15, 1, '太阳诱电：业界领先的大尺寸封装高纹波电流承载能力MLCC', '唯样商城', '', '太阳诱电推出业界领先的大尺寸封装MLCC，兼具高纹波电流承载能力', '太阳诱电：业界领先的大尺寸封装高纹波电流承载能力MLCC', '太阳诱电推出业界领先的大尺寸封装MLCC，兼具高纹波电流承载能力', 0, 0, '', 1, '2026/09/03', 0, 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 插入文章内容
INSERT INTO `eb_article_content` (`nid`, `content`) VALUES
(1, '<p>关于2026年唯样商城积分清零的公告说明，请各位用户注意积分使用期限。</p>'),
(2, '<p>唯样商城正式承接JST-Purple平台服务的公告，为用户提供更优质的服务体验。</p>'),
(3, '<p>关于近期警惕虚假短信的重要声明，请广大用户注意防范诈骗信息。</p>'),
(4, '<p>唯样商城ONEYAC官方简介2026版，全面介绍平台优势与服务。</p>'),
(5, '<p>ROHM开发出高抗浪涌贴片电阻器，以1005尺寸实现0.33W功率，为电子元器件行业带来新的突破。</p>'),
(6, '<p>MLCC（多层陶瓷电容器）作为电子元器件中的重要组成部分，以不到1分钱的成本，正在掌控全球AI产业命脉。</p>'),
(7, '<p>YXC推出YSN8563 RTC与32.768kHz晶振协同设计方案，实现高精度时间管理。</p>'),
(8, '<p>TE Connectivity为您解读Euro NCAP 2026对座椅位置检测的新要求，为汽车安全保驾护航。</p>'),
(9, '<p>详解合金电阻在精密设备中的应用及选型要点，为工程师提供专业参考。</p>'),
(10, '<p>瞻芯第三代SiC MOSFET以优异性能和可靠性，支撑千万颗级交付规模，覆盖全功率场景应用。</p>'),
(11, '<p>Nexperia与Aurobay Technologies探讨下一代电力半导体电气化动力总成解决方案的合作。</p>'),
(12, '<p>探讨下一代功率半导体的挑战及MLCC应用的关键作用，展望行业发展趋势。</p>'),
(13, '<p>YXC-SYNK智能显示屏时钟方案，从多颗晶振到一颗时钟发生器芯片，简化设计、提升性能。</p>'),
(14, '<p>详解RS422与RS485接口的差异及收发芯片选型指南，帮助工程师做出正确选择。</p>'),
(15, '<p>太阳诱电推出业界领先的大尺寸封装MLCC，兼具高纹波电流承载能力、大容量与低高度设计。</p>')
ON DUPLICATE KEY UPDATE `content` = VALUES(`content`);

-- ============================================================
-- PC首页右侧广告位配置
-- 通过系统管理后台 -> 设置 -> 组合数据 -> 添加新数据组 也可以手动创建
-- config_name 必须为 pc_home_ad
-- ============================================================

INSERT INTO `eb_system_group` (`id`, `cate_id`, `name`, `info`, `config_name`, `fields`)
VALUES (NULL, 0, 'PC首页右侧广告位', 'PC首页轮播图右侧的广告位图片，支持3-4个固定图片广告',
        'pc_home_ad',
        '[{\"name\":\"图片\",\"title\":\"pic\",\"type\":\"image\",\"param\":\"\",\"required\":1},{\"name\":\"链接\",\"title\":\"url\",\"type\":\"input\",\"param\":\"\",\"required\":0},{\"name\":\"排序\",\"title\":\"sort\",\"type\":\"number\",\"param\":\"\",\"required\":0},{\"name\":\"状态\",\"title\":\"status\",\"type\":\"radio\",\"param\":\"显示|1\\r\\n隐藏|0\",\"required\":0,\"value\":\"1\"}]')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `info` = VALUES(`info`), `fields` = VALUES(`fields`);

-- ============================================================
-- 供应商合作申请表
-- ============================================================
CREATE TABLE IF NOT EXISTS `eb_partner_apply` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `company_name` varchar(255) NOT NULL DEFAULT '' COMMENT '公司名称',
    `brand` varchar(255) NOT NULL DEFAULT '' COMMENT '商品品牌',
    `category` varchar(50) NOT NULL DEFAULT '' COMMENT '类别(原厂/代理商)',
    `contact_name` varchar(100) NOT NULL DEFAULT '' COMMENT '联系人',
    `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
    `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    `status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '状态 0=待处理 1=已联系 2=已完成',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='供应商合作申请表';

-- ============================================================
-- 商品阶梯价格表
-- ============================================================
CREATE TABLE IF NOT EXISTS `eb_store_product_tiered_pricing` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `product_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '商品ID',
    `unique_id` varchar(64) NOT NULL DEFAULT '' COMMENT 'SKU唯一值(空表示所有SKU)',
    `min_qty` int(10) unsigned NOT NULL DEFAULT 1 COMMENT '起始数量',
    `max_qty` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '结束数量(0=无限)',
    `price` decimal(10,2) unsigned NOT NULL DEFAULT 0.00 COMMENT '阶梯价格',
    `is_del` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否删除',
    `add_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_unique_id` (`unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品阶梯价格表';

-- 演示阶梯价格数据（为部分商品添加示例数据）
-- 为product_id=1的商品添加阶梯价格
INSERT INTO `eb_store_product_tiered_pricing` (`product_id`, `unique_id`, `min_qty`, `max_qty`, `price`, `is_del`) VALUES
(1, '', 1, 9, 1.80, 0),
(1, '', 10, 99, 1.50, 0),
(1, '', 100, 0, 1.20, 0);