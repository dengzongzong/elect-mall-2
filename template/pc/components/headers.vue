<template>
  <div v-if="$route.path !== '/login'">
    <!-- 顶部快捷栏 -->
    <div class="header-top min_wrapper_1200">
      <div class="header-top-inner wrapper_1200 acea-row row-between-wrapper">
        <div class="header-top-left">
          <a href="javascript:void(0);" rel="sidebar" @click="AddFavorite()">
            <span class="iconfont icon-shoucang2"></span>收藏本站
          </a>
        </div>
        <div class="header-top-right acea-row row-middle">
          <nuxt-link :to="{ path: '/user', query: { page_type: 0 } }" class="top-link acea-row row-middle" v-if="$auth.loggedIn">
            <div class="avatar" v-if="$auth.user.avatar">
              <img :src="$auth.user.avatar" />
            </div>
            <span class="line1" style="max-width: 100px">{{ $auth.user.nickname }}</span>
          </nuxt-link>
          <nuxt-link to="/login" class="top-link" v-else>登录/注册</nuxt-link>
          <nuxt-link :to="{ path: '/user/orderList', query: { page_type: 1 } }" class="top-link">我的订单</nuxt-link>
          <nuxt-link :to="{ path: '/user/balance', query: { page_type: 3 } }" class="top-link">我的余额</nuxt-link>
          <nuxt-link :to="{ path: '/user/messageCenter?page_type=2' }" class="top-link">
            <span class="iconfont icon-duoshanghupc-daohuotongzhi"></span>
            消息
          </nuxt-link>
        </div>
      </div>
    </div>
    
    <!-- 主头部：Logo + 搜索 + 快捷操作 -->
    <div class="header-main min_wrapper_1200" v-if="$route.path !== '/goods_search' && $route.path !== '/goods_search/'">
      <div class="header-main-inner wrapper_1200 acea-row row-between-wrapper">
        <div class="logo-area">
          <div class="logo" @click="goHome">
            <img v-if="$store.state.logoUrl" :src="$store.state.logoUrl" alt="logo" />
          </div>
        </div>
        <div class="search-area">
          <div class="search-box">
            <input type="text" placeholder="输入关键字或零件编号" v-model="search" @keyup.enter="submit" />
            <button class="search-btn" @click="submit">
              <span class="iconfont icon-xiazai5"></span>
            </button>
          </div>
        </div>
        <div class="action-area acea-row row-middle">
          <nuxt-link to="/shoppingCart" class="cart-btn">
            <span class="iconfont icon-dingbu-gouwuche"></span>
            <span class="cart-text">购物车</span>
            <span class="cart-count" v-if="$store.state.cartnumber > 0">{{ $store.state.cartnumber }}</span>
          </nuxt-link>
        </div>
      </div>
    </div>
    
    <!-- 主导航菜单 -->
    <div class="header-nav min_wrapper_1200" v-if="$route.path !== '/goods_search' && $route.path !== '/goods_search/'">
      <div class="header-nav-inner wrapper_1200 acea-row row-between-wrapper">
        <div class="nav-menu acea-row row-middle">
          <div class="nav-item" @mouseenter="showCategoryMenu = true" @mouseleave="showCategoryMenu = false">
            <nuxt-link to="/goods_cate" class="nav-link nav-link-category">
              <span class="iconfont icon-menu"></span>产品分类
            </nuxt-link>
            <div class="category-dropdown" v-if="showCategoryMenu" @mouseenter="showCategoryMenu = true" @mouseleave="showCategoryMenu = false">
              <div class="category-list">
                <div class="category-item" v-for="(item, index) in categoryList" :key="index" @mouseenter="enter(index)" @mouseleave="leaveCategory">
                  <nuxt-link :to="{ path: '/goods_cate', query: { cid: item.id } }" class="category-link">
                    <span>{{ item.cate_name }}</span>
                    <span class="iconfont icon-you"></span>
                  </nuxt-link>
                  <div class="sub-category" v-if="current === index && item.children && item.children.length">
                    <div class="sub-category-grid">
                      <div class="sub-category-item" v-for="(sub, subIdx) in item.children" :key="subIdx">
                        <nuxt-link :to="{ path: '/goods_cate', query: { cid: item.id, sid: sub.id } }">
                          {{ sub.cate_name }}
                        </nuxt-link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="nav-item" v-for="(menu, index) in navMenus" :key="index">
            <nuxt-link :to="menu.url" class="nav-link">{{ menu.title }}</nuxt-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "headers",
  data() {
    return {
      search: "",
      userInfo: {},
      showCategoryMenu: false,
      current: -1,
      categoryList: [],
      navMenus: [
        { title: '首页', url: '/' },
        { title: '全部商品', url: '/goods_list' },
        { title: '新品上市', url: '/goods_list?type=3' },
        { title: '品牌专区', url: '/brand_list' },
        { title: 'BOM配单', url: '/bom_copy' },
      ]
    };
  },
  created() {
    if (this.$auth.loggedIn) {
      this.gainCount();
    }
    this.getCategoryList();
  },
  mounted() {},
  methods: {
    goHome() {
      this.$router.push({ path: "/" });
    },
    getCategoryList() {
      this.$axios.get("/category").then(res => {
        this.categoryList = res.data || [];
      }).catch(() => {});
    },
    enter(index) {
      this.current = index;
    },
    leaveCategory() {
      this.current = -1;
    },
    AddFavorite() {
      let url = window.location;
      let title = document.title;
      let ua = navigator.userAgent.toLowerCase();
      if (ua.indexOf("360se") > -1) {
        this.$message("由于360浏览器功能限制，请按 Ctrl+D 手动收藏！");
      } else if (ua.indexOf("msie 8") > -1) {
        window.external.AddToFavoritesBar(url, title);
      } else if (document.all) {
        try {
          window.external.addFavorite(url, title);
        } catch (e) {
          this.$message("您的浏览器不支持,请按 Ctrl+D 手动收藏!");
        }
      } else if (window.sidebar) {
        this.$message("您的浏览器不支持,请按 Ctrl+D 手动收藏!");
      } else {
        this.$message("您的浏览器不支持,请按 Ctrl+D 手动收藏!");
      }
    },
    gainCount: function () {
      let that = this;
      that.$axios
        .get("/cart/count", { params: { numType: 0 } })
        .then((res) => {
          that.$store.commit("cartNum", res.data.count);
        });
    },
    submit() {
      if (this.search.trim() !== "") {
        this.$router.push({
          path: "/goods_search",
          query: { title: this.search.trim() },
        });
        this.search = "";
      } else {
        this.$message.error("请输入要搜索的内容");
      }
    },
  },
};
</script>

<style scoped lang="scss">
// ====== 顶部快捷栏 ======
.header-top {
  width: 100%;
  height: 36px;
  background-color: #333;
  font-size: 12px;
  color: #ccc;
  cursor: pointer;
  .header-top-inner {
    height: 100%;
    a {
      color: #ccc;
      &:hover { color: #fff; }
    }
    .header-top-left {
      a {
        display: flex;
        align-items: center;
        .icon-shoucang2 { margin-right: 5px; }
      }
    }
    .header-top-right {
      .top-link {
        display: flex;
        align-items: center;
        padding: 0 10px;
        position: relative;
        &:hover { color: #fff; }
        & + .top-link:before {
          content: '';
          position: absolute;
          left: 0;
          top: 50%;
          margin-top: -7px;
          width: 1px;
          height: 14px;
          background: rgba(255,255,255,0.15);
        }
        .avatar {
          width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;
          img { width: 100%; height: 100%; border-radius: 50%; }
        }
      }
    }
  }
}

// ====== 主头部 ======
.header-main {
  width: 100%;
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
  .header-main-inner {
    height: 80px;
    display: flex;
    align-items: center;
    .logo-area {
      flex-shrink: 0;
      .logo {
        width: 160px;
        height: 60px;
        cursor: pointer;
        img { width: 100%; height: 100%; object-fit: contain; }
      }
    }
    .search-area {
      flex: 1;
      padding: 0 40px;
      .search-box {
        display: flex;
        height: 44px;
        border: 2px solid #e93323;
        border-radius: 4px;
        overflow: hidden;
        input {
          flex: 1;
          height: 40px;
          border: none;
          outline: none;
          padding: 0 16px;
          font-size: 14px;
          color: #333;
          font-weight: bold;
          &::placeholder { color: #999; font-weight: normal; }
        }
        .search-btn {
          width: 60px;
          height: 40px;
          background: #e93323;
          border: none;
          color: #fff;
          font-size: 18px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          &:hover { background: #d42d1e; }
        }
      }
    }
    .action-area {
      flex-shrink: 0;
      .cart-btn {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        background: #f5f5f5;
        border-radius: 4px;
        color: #333;
        font-size: 14px;
        font-weight: bold;
        position: relative;
        &:hover { background: #eee; }
        .iconfont { font-size: 22px; margin-right: 6px; color: #e93323; }
        .cart-count {
          position: absolute;
          top: 4px;
          right: 8px;
          background: #e93323;
          color: #fff;
          font-size: 11px;
          min-width: 18px;
          height: 18px;
          line-height: 18px;
          text-align: center;
          border-radius: 9px;
          padding: 0 5px;
          font-weight: bold;
        }
      }
    }
  }
}

// ====== 主导航栏 ======
.header-nav {
  width: 100%;
  background: #fff;
  border-bottom: 2px solid #e93323;
  position: sticky;
  top: 0;
  z-index: 100;
  .header-nav-inner {
    height: 44px;
    .nav-menu {
      height: 100%;
      .nav-item {
        height: 100%;
        position: relative;
        .nav-link {
          display: flex;
          align-items: center;
          height: 100%;
          padding: 0 18px;
          font-size: 14px;
          color: #333;
          font-weight: bold;
          text-decoration: none;
          &:hover { color: #e93323; }
          .icon-menu { font-size: 16px; margin-right: 5px; }
        }
        .nav-link-category {
          background: #e93323;
          color: #fff;
          padding: 0 24px;
          &:hover { background: #d42d1e; color: #fff; }
        }
        // 分类下拉菜单
        .category-dropdown {
          position: absolute;
          top: 100%;
          left: 0;
          width: 280px;
          background: #fff;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          z-index: 200;
          .category-list {
            .category-item {
              position: relative;
              .category-link {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 16px;
                font-size: 13px;
                color: #333;
                font-weight: bold;
                &:hover { background: #f5f5f5; color: #e93323; }
                .iconfont { font-size: 10px; color: #999; }
              }
              .sub-category {
                position: absolute;
                left: 100%;
                top: 0;
                width: 600px;
                min-height: 100%;
                background: #fff;
                box-shadow: 4px 4px 12px rgba(0,0,0,0.1);
                padding: 20px;
                z-index: 201;
                .sub-category-grid {
                  display: flex;
                  flex-wrap: wrap;
                  .sub-category-item {
                    width: 33.33%;
                    padding: 6px 10px;
                    a {
                      font-size: 13px;
                      color: #555;
                      font-weight: bold;
                      &:hover { color: #e93323; }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}

// 响应式
@media (max-width: 1200px) {
  .header-main-inner { padding: 0 15px; }
  .header-nav-inner { padding: 0 15px; }
}
</style>