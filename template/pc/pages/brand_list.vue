<template>
  <div class="brand-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1 class="page-title">品牌精选</h1>
        <p class="page-desc">原厂授权代理，正品保障，品质信赖 · 按字母检索品牌</p>
      </div>

      <!-- 字母索引（吸顶） -->
      <div class="letter-index">
        <div class="index-bar">
          <span class="index-label">按字母检索：</span>
          <span
            v-for="letter in letters"
            :key="letter"
            :class="['index-letter', { active: currentLetter === letter }]"
            @click="scrollToLetter(letter)"
          >{{ letter }}</span>
        </div>
      </div>

      <!-- 品牌分区 -->
      <div
        v-for="letter in letters"
        :key="letter"
        :ref="'letter-' + letter"
        class="brand-letter-group"
        v-if="getBrandsByLetter(letter).length"
      >
        <div class="letter-head">
          <span class="alpha">{{ letter }}</span>
          <span class="letter-title">品牌专区</span>
          <span class="letter-count">共 {{ getBrandsByLetter(letter).length }} 个品牌</span>
        </div>
        <div class="brand-grid">
          <div
            class="brand-card"
            v-for="(brand, index) in getBrandsByLetter(letter)"
            :key="index"
            @click="goBrand(brand)"
          >
            <div class="card-brand">
              <span class="brand-name">{{ brand.en }}</span>
              <span class="brand-cn" v-if="brand.cn">/ {{ brand.cn }}</span>
            </div>
            <p class="brand-count">型号数 <span class="count-num">{{ brand.count.toLocaleString() }}</span> 款</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
/* 品牌数据：英文名 / 中文名 / 型号数 / 首字母 */
const RAW = {
  A: [["Amphenol","安费诺",10966],["ABLIC(SII)","精工",3235],["ALPS","阿尔卑斯",1825],["ASDI","艾申迪",3789],["AOTE","奥特",608],["AOS","美国万代",1997],["Allegro","急速微",261],["AISHI","艾华",1522],["AWINIC","艾为",825],["AAV","制照者",200],["Autochips","杰发",226],["ARK","方舟微",156],["ACPA","华格",144],["ACES","宏致",444],["ACAQ","钰邦",106],["AKM","旭化成",10],["AAC","瑞声科技",19],["AVX",null,5921],["APE",null,0]],
  B: [["Brightking","君耀电子",6783],["BORN","伯恩",934],["BXCONN","宝讯",908],["BELLING","上海贝岭",688],["Bridgelux","普瑞光电",4398],["Broadcom","博通",224],["Bencnnt","槟城",352],["BRT","佰鸿工业",343],["BIWIN","佰维存储",35],["BASiC","基本半导体",59],["Bothhand","帛汉",81],["Broadchip","广芯",125]],
  C: [["CCTC","三环",6086],["cjiang","长江微电",5529],["CHILISIN","奇力新",5666],["CJTconn","长江连接器",4169],["Cree","科锐",11],["CYPRESS","赛普拉斯",91],["CYNTEC","乾坤科技",231],["CHIPANALOG","川土微",740],["CRMICRO","华润微",612],["Chipown","芯朋微",136],["Cotronic","柯森",82],["Coilcraft","线艺",282],["ChipNobo","无边界",108],["Coilank","驰兴",395]],
  D: [["Diodes","达尔",10302],["DIOS","迪恩思",931],["DARFON","达方",1773],["Diptronics","圆达",583],["DELTA","台达",109],["Dioo","帝奥微",57],["DEREN","得润电子",71],["DXFUSE","得线",684],["Dailywell","德利威",47]],
  E: [["EIC",null,9224],["Everlight","亿光",3081],["EVER OHMS","天二",1674],["Epson","爱普生",391],["ELNA","伊娜",630],["Exar-MaxLinear","艾科嘉",202],["EYANG","宇阳",400],["E-LINK","灵科",385],["Eastsoft","东软载波",72],["ELATEC","亿联特",45],["ERNI",null,94]],
  F: [["FH","风华",32257],["FOJAN","富捷",14066],["Faratronic","法拉",2298],["FOSAN","富信",1519],["FCom","富士水晶",31960],["FM","复旦微",74],["Firstohm","第一电阻",273],["FORTIOR","峰岹",148],["FSP","全汉",6],["Fuzetec",null,54]],
  G: [["GUANGLAI","广莱",3620],["GP","格瑞宝",1931],["GigaDevice","兆易创新",1978],["GOFORD","谷峰",1098],["Giantec","聚辰",308],["Gainsil","聚洵",184],["GXCAS","中科银河芯",129],["Goermicro","歌尔微",101],["GREDA","格瑞达",33],["Geehy","珠海极海",26]],
  H: [["HRS","广濑",3875],["HCI","杭晶",5993],["HGSEMI","华冠",4262],["HRE","芯声",2597],["HKR","香港电阻",5851],["Holy Stone","禾伸堂",5859],["HJC","华容",1324],["HARTING","浩亭",406],["Honeywell","霍尼韦尔",221],["Hollyland","好利来",904],["HOLTEK","合泰",70],["HIKVISION","海康威视",34],["HXY","华轩阳",344]],
  I: [["IXDI","艾翔迪",3475],["ISOCOM","英国安数光",4548],["ISND","华信安",5923],["INCP",null,746],["Infineon","英飞凌",5544],["Inventchip","瞻芯电子",239],["ISSI","美国芯成",182],["I-CORE","中微爱芯",654],["Innoscience","英诺赛科",110],["Intel","英特尔",64],["ISA","伊莎贝棱",168]],
  J: [["JST",null,8698],["JSMSEMI","杰盛微",6237],["JSCJ","长晶科技",3084],["JXND","嘉兴南电",4156],["JINGDAO","晶导微",3560],["JJW","捷捷微",5366],["JOULWATT","杰华特",453],["JAE","日本航空电子",314],["Jimson","智新",646],["JCET","长电",14],["Johanson","约翰逊",11]],
  K: [["KOA Speer","日本兴亚",3344],["KUU","永裕泰",2392],["kinghelm","金航标",2144],["Kamaya","釜屋电机",963],["Kyocera","京瓷",1613],["KEMET","基美",44506],["KODENSHI","可天士",494],["KDS","大真空",600],["KEFA","科发",44410],["KAMCAP","凯美",330]],
  L: [["L-COM","诺通",30143],["LIZ","丽智",12681],["LRC","乐山无线电",10084],["Leiditech","雷卯电子",4082],["Lelon","立隆",4473],["Lite-On","光宝",2271],["Lextar","隆达",7849],["Littelfuse","美国力特",3874],["LEM","莱姆",1151],["Luminus","朗明纳斯",1166],["LG INNOTEK","乐金伊诺特",3604]],
  M: [["Murata","村田",15636],["MOLEX","莫仕",6699],["MCC","美微科",2581],["Milliohm","毫欧",1564],["MEAN WELL","明纬",648],["MPS","芯源系统",2578],["MSKSEMI","美森科",5214],["Microchip","微芯",1264],["Mitsubishi","三菱",151],["MAGNACHIP","美格纳",352],["Mediatek","联发科",16],["Mersen","美尔森",6]],
  N: [["Nexperia","安世",19956],["NXP","恩智浦",1195],["NCC","贵弥功",1154],["Nichicon","尼吉康",2133],["NCE","新洁能",1361],["Novosense","纳芯微",213],["NATIONSTAR","国星",502],["Nations","国民技术",256],["NVIDIA","英伟达",69],["Navitas","纳微",30],["Nidec","尼得科",74]],
  O: [["OM SENI","欧姆森",3671],["ON","安森美",10982],["OTW","联芯半导体",1242],["OSRAM","欧司朗",3359],["ORIENT","奥伦德",287],["On-Bright","昂宝",282],["Otax",null,215],["OKdo",null,32]],
  P: [["PASTERNACK",null,41055],["Panasonic","松下",30967],["Panjit","强茂",5026],["Prosemi","普森美",370],["Pulse","普思",1201],["Prisemi","芯导",107],["Phoenix Contact","菲尼克斯",894],["PFC Device","节能元件",125],["Potens","博盛",52]],
  Q: [["QLRELAY","群英",162],["QUNXIN MICRO","群芯微",97],["QST","矽睿",31],["Qualcomm-RF360",null,1531]],
  R: [["ROHM","罗姆",58154],["RALEC","旺诠",12486],["Renesas","瑞萨",762],["RUILON","瑞隆源",1675],["Rubycon","红宝石",1807],["Richtek","立锜",2827],["REASUNOS","瑞森",430],["RESI","开步睿思",10297],["REALTEK","瑞昱",69],["RCD","达标电子",413],["Rectron","丽正",26]],
  S: [["Sunlord","顺络",7185],["SUNMATE","森美特",12246],["SAMSUNG","三星",6958],["ST","意法半导体",8391],["SILERGY","矽力杰",1856],["SiTime",null,1832],["STARPOWER","斯达",166],["SHARP","夏普",193],["SILAN","士兰微",461],["SOUTHCHIP","南芯",311],["SCTF","星通时频",4126],["SEEED","矽递",2527]],
  T: [["TDK",null,80004],["TE","泰科",19994],["TA-I","大毅",18524],["Toshiba","东芝",3850],["Taiyo Yuden","太诱",12840],["TI","德州仪器",7674],["Torex","特瑞仕",51253],["TXC","台湾晶技",1431],["TKD","泰晶",590],["Teapo","智宝",878],["Tronlong","创龙",329]],
  U: [["Uni-Ohm","厚声",15427],["UMW","友台",4388],["UTC","友顺",903],["UDF","优迪半导体",494],["UNISOC","紫光展锐",5],["UPI",null,20],["Usenlight","佑胜",104],["UNITEDSIC","联合碳化硅",12]],
  V: [["Vishay","威世",59897],["VIKING","光颉",2910],["VIIYONG","微容",2674],["Vincotech","威科",620],["Vanchip","唯捷创芯",29],["Vanguard","威兆",111]],
  W: [["WORLDPO","沃德披欧",11632],["Walsin","华科",30444],["Walter","华德",1569],["WAY-ON","维安",2291],["WCH","沁恒",160],["Winbond","华邦电子",162],["WeEn","瑞能",640],["WMEC","万明",164],["WINSOK","微硕",928]],
  X: [["XKB Connection","星坤",15567],["XR","祥如",6704],["XYECONN","辛译",4972],["XIANGYEE","湘怡中元",6976],["XBLW","芯伯乐",1467],["XFCN","兴飞",1569],["XINGLIGHT","成兴光",1196],["XtalTQ","恒晶科技",336],["XHSC","小华",87]],
  Y: [["Yageo","国巨",93197],["YXC","扬兴",4505],["YFW","佑风微",4117],["Yangjie","扬杰",1324],["YMIN","上海永铭",1298],["YJX","雅晶鑫",913],["YONGYUTAI","永裕泰",1631],["YDS","横浜",80],["Yint","音特电子",154]],
  Z: [["ZG","中鑫半导体",7549],["ZHE","众昊恩",328],["ZHENGYAN","争妍微",54],["ZMJSEMI","真茂佳",187],["ZIPPY","新巨",22],["Zhongke Naxin","中科纳芯",8],["浙江芯科",null,50],["中电宏业",null,26]]
};

function flatten() {
  const list = [];
  Object.keys(RAW).forEach(letter => {
    RAW[letter].forEach(item => {
      list.push({ en: item[0], cn: item[1], count: item[2], letter });
    });
  });
  return list;
}

export default {
  name: 'brand_list',
  auth: false,
  data() {
    return {
      brandList: flatten(),
      currentLetter: '',
      letters: ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z']
    };
  },
  head() {
    return { title: '品牌专区 - 品牌精选' };
  },
  mounted() {
    this.setupScrollSpy();
  },
  methods: {
    getBrandsByLetter(letter) {
      return this.brandList.filter(b => b.letter === letter);
    },
    scrollToLetter(letter) {
      this.currentLetter = letter;
      const el = this.$refs['letter-' + letter];
      if (el && el[0]) {
        el[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },
    setupScrollSpy() {
      if (!window || typeof window === 'undefined') return;
      const onScroll = () => {
        let active = '';
        for (const letter of this.letters) {
          const el = this.$refs['letter-' + letter];
          if (el && el[0]) {
            const rect = el[0].getBoundingClientRect();
            if (rect.top <= 200) {
              active = letter;
            }
          }
        }
        if (active) this.currentLetter = active;
      };
      window.addEventListener('scroll', onScroll);
      this._scrollHandler = onScroll;
    },
    goBrand(brand) {
      this.$router.push({
        path: '/goods_list',
        query: { brand: brand.en }
      });
    }
  },
  beforeDestroy() {
    if (this._scrollHandler) {
      window.removeEventListener('scroll', this._scrollHandler);
    }
  }
};
</script>

<style scoped lang="scss">
.brand-page {
  background-color: #f5f6f8;
  min-height: 100vh;
  padding: 20px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0;
}

/* 页面标题 */
.page-header {
  background-color: #fff;
  border-radius: 6px;
  padding: 32px 40px;
  margin-bottom: 16px;
  text-align: center;
  border-bottom: 2px solid #e93323;

  .page-title {
    font-size: 28px;
    color: #333;
    margin: 0 0 8px 0;
    letter-spacing: 1px;
  }

  .page-desc {
    font-size: 14px;
    color: #999;
    margin: 0;
    font-weight: normal;
  }
}

/* 字母索引条（吸顶） */
.letter-index {
  background-color: #fff;
  border-radius: 6px;
  padding: 12px 24px;
  margin-bottom: 20px;
  position: sticky;
  top: 44px;
  z-index: 50;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  .index-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
  }

  .index-label {
    font-size: 13px;
    color: #666;
    margin-right: 8px;
    white-space: nowrap;
    font-weight: normal;
  }

  .index-letter {
    display: inline-block;
    min-width: 30px;
    padding: 4px 8px;
    margin: 2px;
    font-size: 13px;
    color: #333;
    cursor: pointer;
    border-radius: 4px;
    text-align: center;
    transition: all 0.2s;
    font-weight: bold;

    &:hover {
      color: #fff;
      background-color: #e93323;
    }

    &.active {
      color: #fff;
      background-color: #e93323;
    }
  }
}

/* 品牌分区 */
.brand-letter-group {
  margin-bottom: 20px;
  scroll-margin-top: 110px;

  .letter-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    background: linear-gradient(90deg, rgba(233, 51, 35, 0.08), rgba(233, 51, 35, 0));
    border-left: 4px solid #e93323;
    border-radius: 6px 6px 0 0;
    margin-bottom: 12px;

    .alpha {
      font-size: 26px;
      font-weight: 800;
      color: #e93323;
      line-height: 1;
    }

    .letter-title {
      font-size: 16px;
      color: #333;
      font-weight: bold;
      margin: 0;
    }

    .letter-count {
      margin-left: auto;
      font-size: 12px;
      color: #999;
      font-weight: normal;
    }
  }
}

.brand-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 10px;
}

.brand-card {
  background-color: #fff;
  border: 1px solid #eaeaea;
  border-radius: 6px;
  padding: 14px 16px;
  cursor: pointer;
  transition: all 0.25s;

  &:hover {
    border-color: #e93323;
    box-shadow: 0 4px 14px rgba(233, 51, 35, 0.12);
    transform: translateY(-2px);
  }

  &:hover .brand-name {
    color: #e93323;
  }

  .card-brand {
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;

    .brand-name {
      font-size: 15px;
      font-weight: bold;
      color: #333;
      transition: color 0.2s;
      word-break: break-all;
    }

    .brand-cn {
      font-size: 12px;
      color: #999;
      font-weight: normal;
    }
  }

  .brand-count {
    margin-top: 6px;
    font-size: 12px;
    color: #999;
    font-weight: normal;

    .count-num {
      color: #e93323;
      font-weight: bold;
    }
  }
}

@media (max-width: 768px) {
  .page-header { padding: 24px 16px; }
  .page-title { font-size: 20px; }
  .brand-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
  .index-letter { min-width: 24px; padding: 3px 5px; font-size: 12px; }
}
</style>