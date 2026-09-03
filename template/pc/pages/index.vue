<template>
  <div class="digikey-home">
    <!-- Banner 轮播区域（全宽大图） -->
    <div class="banner-section">
      <div class="banner-container">
        <client-only>
          <div v-swiper:mySwiper="swiperOption">
            <div class="swiper-wrapper">
              <nuxt-link
                :to="item.url === undefined ? '' : item.url"
                class="swiper-slide"
                v-for="(item, index) in swiperData"
                :key="index"
                v-show="index < 10"
              >
                <img :src="item.image" alt="banner" />
              </nuxt-link>
            </div>
            <div
              class="swiper-pagination paginationBanner"
              slot="pagination"
            ></div>
            <div class="swiper-button-prev" slot="pagination"></div>
            <div class="swiper-button-next" slot="pagination"></div>
          </div>
        </client-only>
      </div>
    </div>

    <!-- 产品分类入口 -->
    <div class="container category-section">
      <div class="section-title">
        <h2>产品分类</h2>
      </div>
      <div class="category-grid acea-row row-top" @mouseleave="leave()">
        <div class="category-sidebar">
          <div
            class="category-item acea-row row-between-wrapper"
            :class="index === current ? 'active' : ''"
            v-for="(item, index) in categoryList"
            :key="index"
            v-if="index < 10"
            @mouseenter="enter(index)"
          >
            <div class="category-name">{{ item.cate_name }}</div>
            <div class="iconfont icon-you"></div>
          </div>
        </div>
        <div class="category-dropdown scale-up-hor-left" v-if="seen">
          <div class="subcategory-grid acea-row row-top">
            <div
              @click="goCate(item, index)"
              class="subcategory-item acea-row row-middle"
              v-for="(item, index) in categoryCurrent.children"
              :key="index"
            >
              <div class="subcategory-pic">
                <img :src="item.pic" v-if="item.pic" />
                <img src="~assets/images/no_sort.jpg" alt="" v-else />
              </div>
              <div class="subcategory-name">{{ item.cate_name }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 特色内容区域 -->
    <div class="container featured-section">
      <div class="featured-grid">
        <!-- 秒杀区域作为特色横幅 -->
        <div v-if="seckillList.length" class="featured-banner featured-left">
          <div class="featured-content">
            <h3 class="featured-title">限时秒杀</h3>
            <div class="featured-time">
              <span>{{ currentTime }}</span>点场
            </div>
            <div class="featured-desc">距离结束还剩</div>
            <countDown
              :is-day="false"
              :tip-text="' '"
              :day-text="' '"
              :hour-text="' : '"
              :minute-text="' : '"
              :second-text="' '"
              :datatime="datatime"
            ></countDown>
            <nuxt-link to="/seckill" class="featured-btn">立即抢购</nuxt-link>
          </div>
        </div>
        <!-- 诚意推荐 -->
        <div class="featured-right">
          <div
            class="featured-card top-card"
            v-if="Object.keys(boutiqueOne).length"
            @click="goDetail(boutiqueOne)"
          >
            <div class="card-content">
              <h4>诚意推荐</h4>
              <p class="card-desc">{{ boutiqueOne.store_info }}</p>
              <div class="card-price">
                <span class="price-main">¥{{ boutiqueOne.price }}</span>
                <span class="price-origin">¥{{ boutiqueOne.ot_price }}</span>
              </div>
            </div>
            <div class="card-image">
              <img :src="boutiqueOne.image" v-if="boutiqueOne.image" />
              <img src="~assets/images/no_goods.jpg" alt="" v-else />
            </div>
          </div>
          <div class="featured-cards-row">
            <div
              class="featured-small-card"
              v-for="(item, index) in boutiqueTwo"
              :key="index"
              @click="goDetail(item)"
            >
              <h5>{{ item.store_name }}</h5>
              <div class="small-card-price">
                <span class="price-main">¥{{ item.price }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 工具/服务区域 -->
    <div class="container tools-section">
      <div class="tools-grid">
        <div class="tools-column">
          <h3 class="tools-column-title">工具</h3>
          <ul class="tools-list">
            <li><a href="#">在线换算器</a></li>
            <li><a href="#">Scheme It</a></li>
            <li><a href="#">参考设计库</a></li>
            <li><a href="#">对照检索</a></li>
          </ul>
        </div>
        <div class="tools-column">
          <h3 class="tools-column-title">服务</h3>
          <ul class="tools-list">
            <li><a href="#">零件追踪</a></li>
            <li><a href="#">数字化解决方案</a></li>
            <li><a href="#">设计与集成服务</a></li>
            <li><a href="#">产品服务</a></li>
          </ul>
        </div>
        <div class="tools-column">
          <h3 class="tools-column-title">内容</h3>
          <ul class="tools-list">
            <li><a href="#">新产品</a></li>
            <li><a href="#">技术论坛</a></li>
            <li><a href="#">产品培训库</a></li>
            <li><a href="#">视频库</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- 首发新品 / 特色产品轮播 -->
    <div class="container featured-products-section" v-if="newGoods.length">
      <div class="section-header">
        <h2 class="section-title">首发新品</h2>
        <nuxt-link
          :to="{ path: '/goods_list', query: { type: 3 } }"
          class="more-link"
        >
          更多<span class="iconfont icon-you"></span>
        </nuxt-link>
      </div>
      <div class="products-carousel">
        <div v-swiper:myScroll="swiperScroll">
          <div class="swiper-wrapper">
            <div
              class="swiper-slide product-slide"
              v-for="(item, index) in newGoods"
              :key="index"
              @click="goDetail(item)"
            >
              <div class="product-card">
                <div class="product-image">
                  <img :src="item.image" v-if="item.image" />
                  <img src="~assets/images/no_goods.jpg" alt="" v-else />
                </div>
                <div class="product-info">
                  <h4 class="product-name line1">{{ item.store_name }}</h4>
                  <div class="product-price">
                    <span class="label">优惠价</span>
                    <span class="price-main">¥{{ item.price }}</span>
                    <span class="price-origin">¥{{ item.ot_price }}</span>
                  </div>
                  <div class="product-sales">已抢 {{ item.sales }}{{ item.unit_name }}</div>
                </div>
              </div>
            </div>
          </div>
          <div class="swiper-button-prev" slot="pagination"></div>
          <div class="swiper-button-next" slot="pagination"></div>
        </div>
      </div>
    </div>

    <!-- 分类产品区域 -->
    <div class="container category-products-section">
      <div
        class="category-block"
        v-for="(item, index) in classifyList"
        :key="index"
      >
        <div class="category-block-header">
          <h3>{{ item.cate_name }}</h3>
          <nuxt-link
            :to="{
              path: '/goods_cate',
              query: { cid: item.id, cidIndex: index }
            }"
            class="more-link"
          >
            更多<span class="iconfont icon-you"></span>
          </nuxt-link>
        </div>
        <div class="category-products acea-row row-top">
          <div class="category-main-image" @click="goCateMain(item)">
            <img :src="item.big_pic" v-if="item.big_pic" />
            <img src="~assets/images/no_goods.jpg" alt="" v-else />
          </div>
          <div
            class="product-item"
            v-for="(itemn, indexn) in item.productList"
            :key="indexn"
            @click="goDetail(itemn)"
          >
            <div class="product-item-image">
              <img :src="itemn.image" v-if="itemn.image" />
              <img src="~assets/images/no_goods.jpg" alt="" v-else />
            </div>
            <div class="product-item-info">
              <h4 class="product-item-name line2">{{ itemn.store_name }}</h4>
              <div class="product-item-price">
                <div>
                  <span class="price-main">¥{{ itemn.price }}</span>
                  <span class="price-origin">¥{{ itemn.ot_price }}</span>
                </div>
                <div class="coupon-tag" v-if="itemn.checkCoupon">券</div>
              </div>
              <div class="product-item-meta acea-row row-between-wrapper">
                <span>{{ itemn.sales }}人付款</span>
                <span>{{ itemn.star }}分</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 加载更多 -->
      <div
        class="loading-more acea-row row-center-wrapper"
        v-if="classifyList.length && classifyList.length >= limit"
      >
        <span class="loading iconfont icon-jiazai" v-if="!pullRefreshss"></span>
        {{ title }}
      </div>
    </div>

    <!-- 底部介绍区域 -->
    <div class="footer-about">
      <div class="container">
        <div class="about-content">
          <div class="about-block">
            <h4>授权分销</h4>
            <p>汇聚数千家领先厂商，铸就坚实全球供应体系，打造买家信心之选</p>
          </div>
          <div class="about-block">
            <h4>顶级支持</h4>
            <p>提供全套设计和采购工具，助您一路前行</p>
          </div>
          <div class="about-block">
            <h4>品类广博</h4>
            <p>超过300万种产品型号，随时满足您的各种需求</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import countDown from "@/components/countDown";
export default {
  name: "index",
  auth: false,
  components: {
    countDown
  },
  data() {
    return {
      seen: false,
      current: -1,
      swiperData: [],
      categoryList: [],
      categoryCurrent: {},
      datatime: 0,
      dataStatus: 0,
      currentTime: "00:00",
      seckillList: [],
      boutiqueOne: {},
      boutiqueTwo: [],
      newGoods: [],
      classifyList: [], //初始数据代码
      pullRefreshss: true,
      page: 1, //代表页面的初始页数
      limit: 3,
      scollY: null, // 离底部距离有多少
      pageTotal: 0, //总页数
      title: "下拉加载更多",
      swiperOption: {
        pagination: {
          el: ".paginationBanner",
          clickable: true
        },
        navigation: {
          nextEl: ".swiper-button-next",
          prevEl: ".swiper-button-prev"
        },
        autoplay: {
          disableOnInteraction: false,
          delay: 5000
        },
        loop: true,
        speed: 1000,
        observer: true,
        observeParents: true
      },
      swiperScroll: {
        navigation: {
          nextEl: ".swiper-button-next",
          prevEl: ".swiper-button-prev"
        },
        freeMode: true,
        freeModeMomentum: false,
        slidesPerView: "auto",
        observer: true,
        observeParents: true
      },
      siteName: "",
      time_id: 0
    };
  },
  async asyncData({ app }) {
    let [categoryMsg, seckillMsg, indexMsg, bannerMsg] = await Promise.all([
      //获取banner分类
      app.$axios.get("/category"),
      app.$axios.get("/seckill/index"),
      app.$axios.get("/index"),
      app.$axios.get("/pc/get_banner")
    ]);
    return {
      categoryList: categoryMsg.data,
      seckillTime: seckillMsg.data,
      boutiqueOne: indexMsg.data.info.bastList.length
        ? indexMsg.data.info.bastList.shift()
        : {},
      boutiqueTwo: indexMsg.data.info.bastList,
      newGoods: indexMsg.data.info.firstList,
      // logoUrl: indexMsg.data.logoUrl,
      swiperData: bannerMsg.data.list,
      siteName: indexMsg.data.site_name
    };
  },
  fetch({ store }) {
    store.commit("isHeader", true);
    store.commit("isFooter", true);
  },
  head() {
    return {
      title: this.siteName
    };
  },
  created() {
    this.getClassifyList();
    // this.$store.commit('logo', this.logoUrl);
    // this.$cookies.set('logo',this.logoUrl);
  },
  mounted() {
    this.getSeckillIndexTime();
    this.pullRefresh();
  },
  beforeDestroy() {
    window.onscroll = null;
  },
  methods: {
    goCate(item, index) {
      this.$router.push({
        path: "/goods_cate",
        query: {
          cid: this.categoryCurrent.id,
          sid: item.id,
          cidIndex: this.current,
          sidIndex: index
        }
      });
    },
    goCateMain(item) {
      this.$router.push({
        path: "/goods_cate",
        query: {
          cid: item.id
        }
      });
    },
    getSeckillIndexTime() {
      let seckillTime = this.seckillTime;
      if (seckillTime.seckillTimeIndex < 0) return;
      this.datatime =
        seckillTime.seckillTime[seckillTime.seckillTimeIndex].stop;
      this.dataStatus =
        seckillTime.seckillTime[seckillTime.seckillTimeIndex].status;
      this.currentTime =
        seckillTime.seckillTime[seckillTime.seckillTimeIndex].time;
      this.time_id = seckillTime.seckillTime[seckillTime.seckillTimeIndex].id;
      this.$axios
        .get("/seckill/list/" + this.time_id, {
          page: 1,
          limit: 10,
          type: "index"
        })
        .then(res => {
          this.seckillList = res.data;
        });
    },
    enter(index) {
      this.seen = true;
      this.current = index;
      this.categoryCurrent = this.categoryList[index];
    },
    leave() {
      this.seen = false;
      this.current = -1;
    },
    getClassifyList() {
      let _this = this,
        currentPage = { page: this.page, limit: this.limit };
      _this.$axios
        .get("/pc/get_category_product", {
          params: currentPage
        })
        .then(function(res) {
          _this.pageTotal = res.data.count;
          // 请求完成后，把得到的数据拼接到当前dom里面
          _this.classifyList = _this.classifyList.concat(res.data.list);
        })
        .catch(function(err) {
          _this.$message.error(err);
        });
    },
    // 下拉加载ajax
    pullRefresh: function() {
      let _this = this;
      window.onscroll = function() {
        _this.scrollChange();
      };
    },
    scrollChange: function() {
      var _this = this;
      this.scollY =
        this.comsys.getScrollTop() +
        this.comsys.getWindowHeight() -
        this.comsys.getScrollHeight();
      // 把下拉刷新置为false，防止多次请求
      if (this.scollY >= -50) {
        if (this.page > Math.ceil(this.pageTotal / this.limit)) {
          this.title = "已没有更多数据";
          this.pullRefreshss = true;
          return false;
        }
        if (!this.pullRefreshss) {
          return false;
        }
        _this.pullRefreshss = false;
        this.title = "正在加载中....";
        // 加页码数
        this.page++;
        _this.getClassifyList();
      } else {
        // 其他时候把下拉刷新置为true
        _this.pullRefreshss = true;
        this.title = "下拉加载更多";
      }
    },
    goDetail(item) {
      let path = item.presale
        ? (path = {
            path: "/goods_presell_detail",
            query: {
              id: item.id
            }
          })
        : (path = { path: `/goods_detail/${item.id}` });
      this.$router.push(path);
    },
    goSeckillDetail: function(id, productId, time, status) {
      this.$router.push({
        path: "/goods_seckill_detail",
        query: {
          id: id,
          productId: productId,
          time: time,
          status: status,
          time_id: this.time_id
        }
      });
    }
  }
};
</script>

<style scoped lang="scss">
.digikey-home {
  background-color: #f5f5f5;
  font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
  font-weight: bold;
  min-height: 100vh;
  padding: 0;
  margin: 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Banner 轮播区域 */
.banner-section {
  width: 100%;
  background-color: #fff;
  margin-bottom: 24px;
}

.banner-container {
  width: 100%;
  height: 400px;
  position: relative;
  overflow: hidden;

  .swiper-slide {
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }
}

/* 产品分类入口 */
.category-section {
  margin-bottom: 24px;

  .section-title {
    margin-bottom: 16px;

    h2 {
      font-size: 20px;
      color: #333;
      font-weight: bold;
      padding-bottom: 8px;
      border-bottom: 2px solid #e93323;
    }
  }

  .category-grid {
    background-color: #fff;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
  }

  .category-sidebar {
    width: 240px;
    float: left;
    background-color: #f8f8f8;
  }

  .category-item {
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid #eaeaea;
    transition: all 0.3s;

    &.active,
    &:hover {
      background-color: #e93323;
      color: #fff;
    }

    .category-name {
      font-size: 14px;
      line-height: 20px;
    }

    .iconfont {
      font-size: 12px;
    }
  }

  .category-dropdown {
    flex: 1;
    padding: 16px;
    background-color: #fff;
    min-height: 300px;
  }

  .subcategory-grid {
    display: flex;
    flex-wrap: wrap;

    .subcategory-item {
      width: 25%;
      padding: 12px;
      cursor: pointer;

      &:hover {
        .subcategory-name {
          color: #e93323;
        }
      }

      .subcategory-pic {
        width: 50px;
        height: 50px;
        margin-right: 12px;

        img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          border-radius: 4px;
        }
      }

      .subcategory-name {
        font-size: 14px;
        color: #333;
        transition: color 0.3s;
      }
    }
  }
}

/* 特色内容区域 */
.featured-section {
  margin-bottom: 24px;

  .featured-grid {
    display: flex;
    gap: 16px;
  }

  .featured-left {
    flex: 0 0 280px;
  }

  .featured-right {
    flex: 1;
  }

  .featured-banner {
    background: linear-gradient(135deg, #e93323 0%, #ff5a4a 100%);
    border-radius: 4px;
    padding: 32px 24px;
    color: #fff;
    height: 100%;
    display: flex;
    align-items: center;

    .featured-content {
      width: 100%;
      text-align: center;

      .featured-title {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 16px;
      }

      .featured-time {
        font-size: 16px;
        margin-bottom: 8px;

        span {
          font-weight: bold;
          font-size: 20px;
        }
      }

      .featured-desc {
        font-size: 14px;
        margin: 12px 0;
        opacity: 0.9;
      }

      .featured-btn {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 32px;
        background-color: #fff;
        color: #e93323;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s;

        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
      }
    }
  }

  .top-card {
    background-color: #fff;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: box-shadow 0.3s;

    &:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .card-content {
      flex: 1;
      padding-right: 20px;

      h4 {
        font-size: 18px;
        color: #333;
        margin-bottom: 8px;
      }

      .card-desc {
        font-size: 14px;
        color: #666;
        margin-bottom: 12px;
        line-height: 1.5;
      }

      .card-price {
        .price-main {
          font-size: 22px;
          color: #e93323;
          font-weight: bold;
        }

        .price-origin {
          font-size: 14px;
          color: #999;
          text-decoration: line-through;
          margin-left: 8px;
          font-weight: normal;
        }
      }
    }

    .card-image {
      width: 140px;
      height: 140px;

      img {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }
    }
  }

  .featured-cards-row {
    display: flex;
    gap: 12px;
  }

  .featured-small-card {
    flex: 1;
    background-color: #fff;
    border-radius: 4px;
    padding: 16px;
    cursor: pointer;
    transition: box-shadow 0.3s;

    &:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    h5 {
      font-size: 14px;
      color: #333;
      margin-bottom: 8px;
      line-height: 1.4;
    }

    .small-card-price {
      .price-main {
        font-size: 18px;
        color: #e93323;
      }
    }
  }
}

/* 工具/服务区域 */
.tools-section {
  background-color: #fff;
  border-radius: 4px;
  padding: 24px 0;
  margin-bottom: 24px;

  .tools-grid {
    display: flex;
  }

  .tools-column {
    flex: 1;
    padding: 0 24px;
    border-right: 1px solid #eaeaea;

    &:last-child {
      border-right: none;
    }

    .tools-column-title {
      font-size: 16px;
      color: #333;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 2px solid #e93323;
    }

    .tools-list {
      list-style: none;
      padding: 0;
      margin: 0;

      li {
        margin-bottom: 8px;

        a {
          font-size: 14px;
          color: #666;
          text-decoration: none;
          transition: color 0.3s;

          &:hover {
            color: #e93323;
          }
        }
      }
    }
  }
}

/* 特色产品区域 */
.featured-products-section {
  margin-bottom: 24px;

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e93323;

    .section-title {
      font-size: 20px;
      color: #333;
      font-weight: bold;
      margin: 0;
    }

    .more-link {
      font-size: 14px;
      color: #666;
      text-decoration: none;

      .iconfont {
        font-size: 10px;
        margin-left: 4px;
      }

      &:hover {
        color: #e93323;
      }
    }
  }

  .products-carousel {
    background-color: #fff;
    border-radius: 4px;
    padding: 20px;

    .product-slide {
      width: 220px;

      .product-card {
        background-color: #fff;
        border-radius: 4px;
        overflow: hidden;
        cursor: pointer;
        transition: box-shadow 0.3s;

        &:hover {
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .product-image {
          width: 192px;
          height: 192px;
          margin: 0 auto;

          img {
            width: 100%;
            height: 100%;
            object-fit: contain;
          }
        }

        .product-info {
          padding: 12px;

          .product-name {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
          }

          .product-price {
            margin-bottom: 10px;

            .label {
              display: inline-block;
              padding: 2px 6px;
              background-color: #e93323;
              color: #fff;
              font-size: 12px;
              border-radius: 2px;
              margin-right: 6px;
            }

            .price-main {
              font-size: 18px;
              color: #e93323;
            }

            .price-origin {
              font-size: 12px;
              color: #999;
              text-decoration: line-through;
              margin-left: 6px;
              font-weight: normal;
            }
          }

          .product-sales {
            font-size: 12px;
            color: #999;
          }
        }
      }
    }
  }
}

/* 分类产品区域 */
.category-products-section {
  margin-bottom: 24px;

  .category-block {
    background-color: #fff;
    border-radius: 4px;
    padding: 16px 20px 24px;
    margin-bottom: 20px;

    .category-block-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;

      h3 {
        font-size: 18px;
        color: #333;
        font-weight: bold;
      }

      .more-link {
        font-size: 14px;
        color: #666;
        text-decoration: none;
        padding: 4px 12px;
        border: 1px solid #ddd;
        border-radius: 2px;

        .iconfont {
          font-size: 10px;
          margin-left: 4px;
        }

        &:hover {
          color: #e93323;
          border-color: #e93323;
        }
      }
    }

    .category-products {
      .category-main-image {
        width: 400px;
        margin-right: 20px;
        cursor: pointer;

        img {
          width: 100%;
          height: auto;
          object-fit: contain;
          border-radius: 4px;
        }
      }

      .product-item {
        width: 220px;
        background-color: #fff;
        border-radius: 4px;
        padding: 12px;
        margin-bottom: 16px;
        cursor: pointer;
        transition: box-shadow 0.3s;

        &:hover {
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .product-item-image {
          width: 192px;
          height: 192px;
          margin: 0 auto 12px;

          img {
            width: 100%;
            height: 100%;
            object-fit: contain;
          }
        }

        .product-item-info {
          .product-item-name {
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
          }

          .product-item-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;

            .price-main {
              font-size: 20px;
              color: #e93323;
            }

            .price-origin {
              font-size: 12px;
              color: #999;
              text-decoration: line-through;
              font-weight: normal;
            }

            .coupon-tag {
              width: 20px;
              height: 20px;
              line-height: 20px;
              text-align: center;
              font-size: 12px;
              background: rgba(233, 51, 35, 0.15);
              color: #e93323;
              border-radius: 2px;
            }
          }

          .product-item-meta {
            font-size: 12px;
            color: #aaa;
          }
        }
      }
    }
  }

  .loading-more {
    padding: 20px 0;
    font-size: 14px;
    color: #666;

    .loading {
      margin-right: 8px;
      animation: rotate 1s linear infinite;
    }
  }
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* 底部介绍区域 */
.footer-about {
  background-color: #fff;
  border-top: 1px solid #eaeaea;
  padding: 40px 0;
  margin-top: 40px;

  .about-content {
    display: flex;
    justify-content: space-around;
  }

  .about-block {
    text-align: center;
    padding: 0 20px;

    h4 {
      font-size: 18px;
      color: #333;
      margin-bottom: 12px;
    }

    p {
      font-size: 14px;
      color: #666;
      line-height: 1.6;
      font-weight: normal;
    }
  }
}

.scale-up-hor-left {
  -webkit-animation: scale-up-hor-left 0.4s cubic-bezier(0.39, 0.575, 0.565, 1)
    both;
  animation: scale-up-hor-left 0.4s cubic-bezier(0.39, 0.575, 0.565, 1) both;
}

@-webkit-keyframes scale-up-hor-left {
  0% {
    -webkit-transform: scaleX(0.4);
    transform: scaleX(0.4);
    -webkit-transform-origin: 0 0;
    transform-origin: 0 0;
  }
  100% {
    -webkit-transform: scaleX(1);
    transform: scaleX(1);
    -webkit-transform-origin: 0 0;
    transform-origin: 0 0;
  }
}

@keyframes scale-up-hor-left {
  0% {
    -webkit-transform: scaleX(0.4);
    transform: scaleX(0.4);
    -webkit-transform-origin: 0 0;
    transform-origin: 0 0;
  }
  100% {
    -webkit-transform: scaleX(1);
    transform: scaleX(1);
    -webkit-transform-origin: 0 0;
    transform-origin: 0 0;
  }
}
</style>
