<template>
  <div class="authorized-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1 class="page-title">品牌授权代理</h1>
        <p class="page-desc">原厂正品授权，专业电子元器件分销</p>
      </div>

      <!-- 授权品牌列表 -->
      <div class="brand-section">
        <div class="brand-grid">
          <div
            v-for="brand in brandList"
            :key="brand.id"
            class="brand-card"
          >
            <a :href="`/brand/${brand.id}.html`" target="_blank">
              <div class="brand-info">
                <span class="brand-name">{{ brand.name }}</span>
                <span class="brand-arrow">›</span>
              </div>
            </a>
            <a
              v-if="brand.hasCert"
              :href="`/brand/authorized_info?brandId=${brand.id}`"
              target="_blank"
              class="cert-link"
            >查看授权证书</a>
          </div>
        </div>
      </div>

      <!-- 合作伙伴 -->
      <div class="partner-section">
        <h2 class="section-title">合作伙伴</h2>
        <div class="partner-list">
          <div
            v-for="(partner, index) in partnerList"
            :key="index"
            class="partner-card"
          >
            <div class="partner-image">
              <img :src="partner.image" :alt="partner.name" />
            </div>
            <div class="partner-content">
              <div class="partner-header">
                <a :href="partner.link" target="_blank">
                  <img :src="partner.logo" :alt="partner.name" class="partner-logo" />
                </a>
              </div>
              <p class="partner-desc">{{ partner.desc }}</p>
              <p class="partner-app"><strong>应用领域：</strong>{{ partner.app }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 优势特点 -->
      <div class="feature-section">
        <div class="feature-list">
          <div class="feature-item">
            <div class="feature-icon">优</div>
            <div class="feature-text">
              <h3>原厂授权</h3>
              <p>正品保障</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">快</div>
            <div class="feature-text">
              <h3>自营现货</h3>
              <p>极速发货</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">全</div>
            <div class="feature-text">
              <h3>品类齐全</h3>
              <p>一站采购</p>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">省</div>
            <div class="feature-text">
              <h3>满299包邮</h3>
              <p>省心省事</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
// 授权品牌列表（按照唯样官网顺序整理）
const BRAND_DATA = [
  { id: 874, name: 'TDK', cn: '', hasCert: true },
  { id: 875, name: 'YAGEO(国巨)', cn: '', hasCert: true },
  { id: 883, name: 'KEMET(基美)', cn: '', hasCert: true },
  { id: 923, name: 'TOREX(特瑞仕)', cn: '', hasCert: true },
  { id: 878, name: 'Panasonic(松下)', cn: '', hasCert: true },
  { id: 994, name: 'ROHM(罗姆)', cn: '', hasCert: true },
  { id: 1062, name: 'TE Connectivity(泰科)', cn: '', hasCert: true },
  { id: 886, name: 'TAIYO YUDEN(太阳诱电)', cn: '', hasCert: true },
  { id: 1114, name: 'NDK(电波工业)', cn: '', hasCert: true },
  { id: 1152, name: 'AOS(美国万代)', cn: '', hasCert: true },
  { id: 1584, name: 'Nexperia(安世半导体)', cn: '', hasCert: true },
  { id: 1069, name: 'KORCHIP(高奇普)', cn: '', hasCert: true },
  { id: 1085, name: 'LUMINUS(朗明纳斯)', cn: '', hasCert: true },
  { id: 942, name: 'LIZ(台湾丽智)', cn: '', hasCert: true },
  { id: 944, name: 'TAI-TECH(台庆)', cn: '', hasCert: true },
  { id: 879, name: 'CHILISIN(奇力新)', cn: '', hasCert: true },
  { id: 1216, name: 'SUSUMU(进工业)', cn: '', hasCert: true },
  { id: 1043, name: 'LRC(乐山无线电)', cn: '', hasCert: true },
  { id: 976, name: 'TA-I(大毅)', cn: '', hasCert: true },
  { id: 935, name: 'RALEC(旺诠)', cn: '', hasCert: true },
  { id: 1179, name: 'Panjit(强茂)', cn: '', hasCert: true },
  { id: 965, name: 'Kyocera(京瓷)', cn: '', hasCert: true },
  { id: 1087, name: 'ISND(华信安)', cn: '', hasCert: true },
  { id: 3391, name: 'SEEED(矽递科技)', cn: '', hasCert: true },
  { id: 4877, name: 'Tronlong®(创龙)', cn: '', hasCert: true },
  { id: 3568, name: 'YXC(扬兴)', cn: '', hasCert: true },
  { id: 4906, name: 'MDD(辰达行)', cn: '', hasCert: true },
  { id: 5972, name: 'Metorage(星火半导体)', cn: '', hasCert: true },
  { id: 6054, name: 'HJC(华容)', cn: '', hasCert: true },
  { id: 6038, name: 'Truesemi(信安)', cn: '', hasCert: true },
  { id: 6058, name: 'SOFNG(硕方)', cn: '', hasCert: true },
  { id: 6070, name: 'ICMAN(晶尊微电子)', cn: '', hasCert: true },
  { id: 4889, name: '帛汉(帛漢)', cn: '', hasCert: true },
  { id: 6086, name: 'Coilank(驰兴)', cn: '', hasCert: true },
  { id: 6088, name: 'DXFUSE(厦门得线)', cn: '', hasCert: true },
  { id: 6092, name: 'StrongFirst(思硕)', cn: '', hasCert: true },
  { id: 5941, name: 'kinghelm(金航标)', cn: '', hasCert: true },
  { id: 5940, name: 'Slkor(萨科微)', cn: '', hasCert: true },
  { id: 6115, name: 'JGHC(晶光华)', cn: '', hasCert: true },
  { id: 6114, name: 'HONOR(荣誉)', cn: '', hasCert: true },
  { id: 6137, name: 'HCCCap(合众汇能)', cn: '', hasCert: true },
  { id: 1132, name: '福建国光', cn: '', hasCert: true },
  { id: 6299, name: 'AIPULNION(爱浦电子)', cn: '', hasCert: true },
  { id: 6295, name: 'Maswell(迈斯维)', cn: '', hasCert: true },
  { id: 6323, name: 'CRC(创容)', cn: '', hasCert: true },
  { id: 6329, name: 'Silicon Billion(硅兆)', cn: '', hasCert: true },
  { id: 6369, name: 'Leader(立得)', cn: '', hasCert: true },
  { id: 6352, name: 'CODACA(科达嘉)', cn: '', hasCert: true },
  { id: 6381, name: 'RESI(开步睿思)', cn: '', hasCert: true },
  { id: 6368, name: 'XINGLIGHT(成兴光)', cn: '', hasCert: true },
  { id: 5947, name: 'NEXT', cn: '', hasCert: true },
  { id: 5524, name: 'JJW(捷捷微)', cn: '', hasCert: true },
  { id: 6741, name: 'COSINE(科山芯创)', cn: '', hasCert: true },
  { id: 6746, name: 'MSKSEMI(美森科)', cn: '', hasCert: true },
  { id: 6775, name: 'FOJAN(富捷)', cn: '', hasCert: true },
  { id: 6777, name: 'Gangyuan(港源)', cn: '', hasCert: true },
  { id: 6690, name: 'Cenker(岑科)', cn: '', hasCert: true },
  { id: 1001, name: 'EVEROHMS(天二)', cn: '', hasCert: true },
  { id: 6325, name: 'RUILON(瑞隆源)', cn: '', hasCert: true },
  { id: 6713, name: 'Semiment(赛卓电子)', cn: '', hasCert: true },
  { id: 6375, name: 'Linearin(先积)', cn: '', hasCert: true },
  { id: 877, name: 'FH(风华)', cn: '', hasCert: true },
  { id: 6330, name: 'Gainsil(聚洵)', cn: '', hasCert: true },
  { id: 6835, name: 'HOLLY(好利来)', cn: '', hasCert: true },
  { id: 6847, name: 'SEP(威旺)', cn: '', hasCert: true },
  { id: 6091, name: 'WINSOK(微硕)', cn: '', hasCert: true },
  { id: 6727, name: 'UMW(友台半导体)', cn: '', hasCert: true },
  { id: 6855, name: 'BNstar(比诺星)', cn: '', hasCert: true },
  { id: 6866, name: 'CONNTEK(昆泰芯微)', cn: '', hasCert: true },
  { id: 7015, name: 'LEACAP(利容)', cn: '', hasCert: true },
  { id: 5407, name: 'GP(格瑞宝)', cn: '', hasCert: true },
  { id: 6867, name: 'HONGWAN(弘湾半导体)', cn: '', hasCert: true },
  { id: 6892, name: 'Autochips(杰发)', cn: '', hasCert: true },
  { id: 7369, name: 'AOTE(奥特)', cn: '', hasCert: true },
  { id: 7394, name: 'FC(方成)', cn: '', hasCert: true },
  { id: 6694, name: 'XKB(星坤)', cn: '', hasCert: true },
  { id: 5832, name: 'L-COM(诺通)', cn: '', hasCert: true },
  { id: 7306, name: 'PASTERNACK', cn: '', hasCert: true },
  { id: 7433, name: 'GrandMicro(有容微)', cn: '', hasCert: true },
  { id: 6812, name: 'Inventchip(瞻芯电子)', cn: '', hasCert: true },
  { id: 7182, name: 'RF-star(信驰达)', cn: '', hasCert: true },
  { id: 1820, name: 'Rectron(丽正)', cn: '', hasCert: true },
  { id: 7375, name: 'Milliohm(毫欧)', cn: '', hasCert: true },
  { id: 7591, name: 'ZG(中鑫半导体)', cn: '', hasCert: true },
  { id: 922, name: 'Vishay(威世)', cn: '', hasCert: true },
  { id: 7604, name: 'JSMSEMI(杰盛微)', cn: '', hasCert: true },
  { id: 7608, name: 'OTW(联芯半导体)', cn: '', hasCert: true },
  { id: 7611, name: 'Toohong(太虹科技)', cn: '', hasCert: true },
  { id: 7615, name: 'Linepri(蓝沛)', cn: '', hasCert: true },
  { id: 7116, name: 'ISA(伊莎贝棱)', cn: '', hasCert: true },
  { id: 912, name: 'Lelon(立隆)', cn: '', hasCert: true },
  { id: 1204, name: 'JST(杰世腾)', cn: '', hasCert: true },
];

// 合作伙伴数据
const PARTNER_DATA = [
  {
    name: '厦门信和达电子有限公司',
    image: 'https://uploadcdn.oneyac.com/attachments/page/brand/sqdl/images/partner-show-1.jpg',
    logo: 'https://uploadcdn.oneyac.com/attachments/page/brand/sqdl/images/partner-logo-1.jpg',
    link: 'http://www.xmholder.com/',
    desc: '厦门信和达电子有限公司成立于2000年，专业从事电子元器件代理销售业务，经过十几年的发展，已先后取得TDK、YAGEO（台湾国巨）、Panasonic、KEMET（美国基美）、Chilisin（台湾奇力新）、TAI-TECH（台湾台庆）、KDS（日本大真空）、H.ELE（台湾加高）、TOREX（日本特瑞仕）、LRC（乐山无线电）、PTTC（台湾聚鼎）、ATO（台湾荣创）、ISND等公司的销售代理权，我司代理的产品被广泛应用于各个电子行业。',
    app: '手机、液晶电视、智能穿戴、无人机、新能源汽车、新能源逆变等行业'
  },
  {
    name: '富昌电子',
    image: 'https://uploadcdn.oneyac.com/attachments/page/brand/sqdl/images/partner-show-2.jpg',
    logo: 'https://uploadcdn.oneyac.com/attachments/page/brand/sqdl/images/partner-logo-2.jpg',
    link: 'javascript:void(0)',
    desc: '富昌电子成立于1968年，是全球领先的电子元器件分销商，也是目前业界公认的最受尊敬和最具创新性的公司之一。富昌电子的总部设在蒙特利尔，经营业务遍布全球40多个国家，为客户提供优质的服务，开发高效、完善的全球供应链解决方案，在业界独具盛名。',
    app: '充电桩、电源驱动、BMS、电源、手表、医疗设备、汽车电子、LED照明、工业电子设备等行业'
  },
  {
    name: 'TME',
    image: 'https://uploadcdn.oneyac.com/attachments/page/brand/sqdl/images/partner-show-3.jpg',
    logo: 'https://uploadcdn.oneyac.com/attachments/page/brand/sqdl/images/partner-logo-3.png',
    link: 'javascript:void(0)',
    desc: 'TME是电子元器件、电器元件、车间设备及工业自动化产品全球最大的分销商之一。公司在波兰总部和其他国家的子公司现有员工近800人。我们为140个国家的数万企业服务，每天发送5000个包裹。其中提供的250000种产品，大多数为电子元器件最重要的制造商的产品。',
    app: '电子元器件、电器元件、车间设备、工业自动化产品'
  }
];

export default {
  name: 'authorized_dealer',
  auth: false,
  data() {
    return {
      brandList: BRAND_DATA,
      partnerList: PARTNER_DATA
    };
  },
  head() {
    return { title: '授权代理 - 品牌授权分销商' };
  }
};
</script>

<style scoped lang="scss">
.authorized-page {
  background-color: #f5f6f8;
  min-height: 100vh;
  padding: 30px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* 页面标题 */
.page-header {
  text-align: center;
  margin-bottom: 32px;

  .page-title {
    font-size: 32px;
    color: #333;
    margin: 0 0 10px 0;
    font-weight: 600;
  }

  .page-desc {
    font-size: 15px;
    color: #999;
    margin: 0;
    font-weight: normal;
  }
}

/* 品牌列表网格 */
.brand-section {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

  .brand-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
  }

  .brand-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: #fcfcfc;
    border: 1px solid #eaeaea;
    border-radius: 6px;
    transition: all 0.2s;

    &:hover {
      border-color: #1890ff;
      background: #f0f8ff;

      .brand-name {
        color: #1890ff;
      }
    }

    a {
      text-decoration: none;
      flex: 1;
    }

    .brand-info {
      display: flex;
      align-items: center;
      justify-content: space-between;

      .brand-name {
        font-size: 15px;
        font-weight: 500;
        color: #333;
        transition: color 0.2s;
      }

      .brand-arrow {
        color: #ccc;
        font-size: 16px;
        font-weight: bold;
      }
    }

    .cert-link {
      font-size: 12px;
      color: #1890ff;
      text-decoration: none;
      margin-left: 12px;
      padding: 4px 10px;
      border: 1px solid #1890ff;
      border-radius: 4px;
      white-space: nowrap;
      transition: all 0.2s;

      &:hover {
        background: #1890ff;
        color: #fff;
      }
    }
  }
}

/* 合作伙伴 */
.partner-section {
  background: #fff;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

  .section-title {
    font-size: 20px;
    color: #333;
    margin: 0 0 24px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
  }

  .partner-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  .partner-card {
    display: flex;
    gap: 20px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f0f0f0;

    &:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .partner-image {
      flex-shrink: 0;
      width: 300px;

      img {
        width: 100%;
        border-radius: 6px;
        display: block;
      }
    }

    .partner-content {
      flex: 1;
      padding: 8px 0;

      .partner-header {
        margin-bottom: 12px;

        .partner-logo {
          max-height: 60px;
          max-width: 200px;
        }
      }

      .partner-desc {
        font-size: 14px;
        color: #666;
        line-height: 1.7;
        margin: 0 0 10px 0;
      }

      .partner-app {
        font-size: 13px;
        color: #888;
        margin: 0;

        strong {
          color: #555;
        }
      }
    }
  }
}

/* 优势特点 */
.feature-section {
  background: #fff;
  border-radius: 8px;
  padding: 32px 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

  .feature-list {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
  }

  .feature-item {
    display: flex;
    align-items: center;
    gap: 16px;
    text-align: center;
    justify-content: center;

    .feature-icon {
      width: 60px;
      height: 60px;
      line-height: 60px;
      text-align: center;
      background: linear-gradient(135deg, #1890ff, #40a9ff);
      color: #fff;
      font-size: 24px;
      font-weight: bold;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .feature-text {
      text-align: left;

      h3 {
        font-size: 16px;
        color: #333;
        margin: 0 0 4px 0;
        font-weight: 600;
      }

      p {
        font-size: 13px;
        color: #999;
        margin: 0;
      }
    }
  }
}

@media (max-width: 992px) {
  .partner-card {
    flex-direction: column;

    .partner-image {
      width: 100% !important;
    }
  }

  .feature-section .feature-list {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .authorized-page {
    padding: 16px 0;
  }

  .page-header .page-title {
    font-size: 24px;
  }

  .brand-section {
    padding: 16px;
  }

  .brand-section .brand-grid {
    grid-template-columns: 1fr;
  }

  .partner-section {
    padding: 16px;
  }

  .feature-section {
    padding: 20px 16px;

    .feature-list {
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
    }

    .feature-item {
      flex-direction: column;
      text-align: center;
      gap: 8px;

      .feature-text {
        text-align: center;
      }
    }
  }
}
</style>
