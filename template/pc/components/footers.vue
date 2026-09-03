<template>
  <div>
    <footer class="footer">
      <div class="wrapper_1200 footer-container">
        <!-- 社交媒体关注区域 -->
        <div class="social-section">
          <h4 class="footer-title">关注我们</h4>
          <div class="social-links">
            <a href="javascript:;" class="social-link" @mouseleave="wxCodeHide">
              <div @mouseenter="wxCode" class="social-item">
                <i class="iconfont icon-weixin4"></i>
                <span>微信公众号</span>
              </div>
              <div class="wx-code-box" v-if="iScode">
                <div class="ewm">
                  <div class="pictrue">
                    <div class="arrow"></div>
                    <img :src="codeUrl" class="bicode" />
                  </div>
                  <div class="tip">扫码关注公众号</div>
                </div>
              </div>
            </a>
            <a href="javascript:;" class="social-link">
              <i class="iconfont icon-weibo"></i>
              <span>微博</span>
            </a>
            <a href="javascript:;" class="social-link">
              <i class="iconfont icon-bilibili"></i>
              <span>B站</span>
            </a>
            <a href="javascript:;" class="social-link">
              <i class="iconfont icon-tencent"></i>
              <span>腾讯视频</span>
            </a>
          </div>
        </div>

        <!-- 信息链接区域 -->
        <div class="info-section">
          <h4 class="footer-title">关于我们</h4>
          <ul class="footer-links">
            <li><a href="javascript:;">公司简介</a></li>
            <li><a href="javascript:;">新闻动态</a></li>
            <li><a href="javascript:;">招贤纳士</a></li>
            <li><a href="javascript:;">联系我们</a></li>
            <li><a href="javascript:;">站点地图</a></li>
          </ul>
        </div>

        <!-- 产品与服务区域 -->
        <div class="info-section">
          <h4 class="footer-title">产品服务</h4>
          <ul class="footer-links">
            <li><a href="javascript:;">产品分类</a></li>
            <li><a href="javascript:;">品牌专区</a></li>
            <li><a href="javascript:;">最新上架</a></li>
            <li><a href="javascript:;">热销产品</a></li>
            <li><a href="javascript:;">特价促销</a></li>
          </ul>
        </div>

        <!-- 联系与支持区域 -->
        <div class="contact-section">
          <h4 class="footer-title">联系与支持</h4>
          <ul class="contact-info">
            <li v-if="companyInfo.contact_number">
              <i class="iconfont icon-dianhua"></i>
              <span>联系电话：{{ companyInfo.contact_number }}</span>
            </li>
            <li v-if="companyInfo.company_address">
              <i class="iconfont icon-dizhi"></i>
              <span>地址：{{ companyInfo.company_address }}</span>
            </li>
            <li><a href="javascript:;">帮助中心</a></li>
            <li><a href="javascript:;">订单查询</a></li>
            <li><a href="javascript:;">售后服务</a></li>
          </ul>
        </div>

        <!-- 友情链接 -->
        <div
          class="links-section"
          v-if="companyInfo.pc_home_links && companyInfo.pc_home_links.length"
        >
          <h4 class="footer-title">友情链接</h4>
          <div class="friend-links">
            <a
              v-for="(item, index) in companyInfo.pc_home_links"
              :href="item.url"
              :key="index"
              target="_blank"
              >{{ item.title }}</a
            >
          </div>
        </div>
      </div>

      <!-- 版权信息区域 -->
      <div class="copyright-wrapper">
        <div class="wrapper_1200">
          <div class="copyright-info">
            <span class="foot-box" v-if="companyInfo.copyright">
              {{ companyInfo.copyright }}
            </span>
            <span class="foot-box" v-else>
              <a href="https://www.crmeb.com" target="_blank"
                >Copyright ©2025 CRMEB. All Rights Reserved</a
              >
            </span>
          </div>
          <div class="record-info">
            <span v-if="companyInfo.record_No">
              <img
                class="beian"
                src="~/assets/images/beian.png"
                alt=""
                srcset=""
              />
              <a :href="companyInfo.icp_url" target="_blank" class="num">{{
                companyInfo.record_No
              }}</a>
            </span>
            <span class="line" v-if="companyInfo.record_No && companyInfo.network_security">|</span>
            <span v-if="companyInfo.network_security">
              <a
                :href="companyInfo.network_security_url"
                target="_blank"
                class="num"
                >{{ companyInfo.network_security }}</a
              >
            </span>
          </div>
        </div>
      </div>
    </footer>
    <div class="floatWindow">
      <div class="list">
        <!-- <div class="item" @click="chatShow">
          <div class="iconfont icon-lianxikefu"></div>
          <div>联系客服</div>
        </div> -->
        <div class="item" @mouseleave="wxCodeHide">
          <div @mouseenter="wxCode">
            <div class="iconfont icon-weixin4"></div>
            <div>关注微信</div>
          </div>
          <div class="itemCon" v-if="iScode">
            <div class="ewm">
              <div class="pictrue">
                <div class="arrow"></div>
                <img :src="codeUrl" class="bicode" />
              </div>
              <div class="tip">扫码关注公众号</div>
            </div>
          </div>
        </div>
        <div class="item" @click="goCart">
          <div class="iconfont icon-cedaohang-gouwuche"></div>
          <div>购物车</div>
        </div>
        <div class="item" @click="goTop">
          <div class="iconfont icon-huidaodingbu1"></div>
          <div>回到顶部</div>
        </div>
      </div>
    </div>
    <div class="kefuIcon" @click="chatShow">
      <div class="pictrue">
        <div class="num" v-if="$auth.loggedIn && $store.state.unreadNum">
          {{ $store.state.unreadNum }}
        </div>
        <img src="~/assets/images/kefuIcon.png" />
      </div>
    </div>
    <chat-room
      v-show="chatOptions.show"
      :chat-options="chatOptions"
      @chat-close="chatClose"
      @socket-open="socketOpen"
      @socket-error="socketError"
    ></chat-room>
  </div>
</template>

<script>
import ChatRoom from "@/components/ChatRoom";
import appChat from "@/mixins/appChat";
export default {
  name: "footers",
  components: {
    ChatRoom
  },
  mixins: [appChat],
  data() {
    return {
      companyInfo: {},
      codeUrl: "",
      iScode: false
    };
  },
  head() {
    return {
      meta: [
        {
          hid: "keywords",
          name: "keywords",
          content: this.companyInfo.site_keywords
        },
        {
          hid: "description",
          name: "description",
          content: this.companyInfo.site_description
        }
      ]
    };
  },
  created() {
    this.getCompanyInfo();
    this.wechatCode();
  },
  mounted() {},
  methods: {
    goTop() {
      (function n() {
        var t = document.documentElement.scrollTop || document.body.scrollTop;
        if (t > 0) {
          document.body.scrollTop = 0;
          document.documentElement.scrollTop = 0;
        }
      })();
    },
    wxCode() {
      this.iScode = true;
    },
    wxCodeHide() {
      this.iScode = false;
    },
    goCart() {
      this.$router.push({ path: "/shoppingCart" });
    },
    wechatCode() {
      this.$axios.get("/pc/get_wechat_qrcode").then(res => {
        this.codeUrl = res.data.wechat_qrcode;
      });
    },
    getCompanyInfo() {
      this.$axios.get("/pc/get_company_info").then(res => {
        this.companyInfo = res.data;
        this.$store.commit("logo", res.data.logoUrl);
        this.$store.commit("homeMenus", res.data.pc_home_menus);
        this.$cookies.set("logo", res.data.logoUrl);
        this.$store.commit("titles", res.data.site_name);
        this.$cookies.set("titles", res.data.site_name);
      });
    }
  }
};
</script>

<style scoped lang="scss">
// 全局字体系列
$font-family: '微软雅黑', 'Microsoft YaHei', 'PingFang SC', 'Helvetica Neue', Arial, sans-serif;

// 颜色变量
$footer-bg: #282828;
$footer-text: #ccc;
$footer-text-light: #999;
$footer-title: #fff;
$footer-link: #aaa;
$footer-link-hover: #fff;
$footer-border: #3a3a3a;
$copyright-bg: #1e1e1e;
$copyright-text: #888;
$accent-color: #e93323;

// 客服图标
.kefuIcon {
  position: fixed;
  right: 9px;
  bottom: 9%;
  width: 56px;
  height: 56px;
  margin-bottom: 300px;
  z-index: 99;
  .pictrue {
    width: 100%;
    height: 100%;
    position: relative;
    .num {
      position: absolute;
      padding: 0 4px;
      height: 16px;
      line-height: 16px;
      border-radius: 10px;
      background-color: #fc4141;
      color: #fff;
      right: 0;
    }
    img {
      width: 100%;
      height: 100%;
    }
  }
}

// 侧边悬浮窗
.floatWindow {
  position: fixed;
  right: 0;
  bottom: 15%;
  width: 70px;
  z-index: 99;
  cursor: pointer;
  background-color: #fff;
  box-shadow: 0 3px 20px rgba(0, 0, 0, 0.06);

  .list {
    .item {
      position: relative;
      width: 100%;
      height: 74px;
      text-align: center;
      font-size: 12px;
      color: #5c5c5c;
      padding: 12px 0;
      &:hover {
        color: #e93323;
      }
      .iconfont {
        margin-bottom: 5px;
        font-size: 25px;
      }
      & ~ .item {
        &:before {
          position: absolute;
          content: " ";
          width: 48px;
          height: 1px;
          background-color: #f0f0f0;
          top: 0;
          left: 50%;
          margin-left: -24px;
        }
      }
      .itemCon {
        right: 100%;
        position: absolute;
        top: 0;
        padding-right: 20px;
        .ewm {
          width: 140px;
          border: 1px solid #eeeeee;
          background-color: #fff;
          padding: 8px 6px;
          .tip {
            font-size: 14px;
            color: #666;
            margin-top: 6px;
          }
          .pictrue {
            width: 126px;
            height: 126px;
            vertical-align: middle;
            position: relative;
            img {
              width: 100%;
              height: 100%;
            }
            .arrow {
              position: absolute;
              right: -16px;
              top: 10px;
              width: 0px;
              height: 0px;
              border: 8px solid transparent;
              border-left-color: #eee;
              &:before {
                position: absolute;
                left: -8px;
                top: -7px;
                content: "";
                width: 0px;
                height: 0px;
                border: 7px solid transparent;
                border-left-color: #fff;
              }
            }
          }
        }
      }
    }
  }
}

// 底部主容器
.footer {
  font-family: $font-family;
  font-weight: bold;
  margin-top: 50px;
  background-color: $footer-bg;
  color: $footer-text;

  .footer-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 50px 0 40px;
  }

  // 通用标题样式
  .footer-title {
    font-size: 16px;
    font-weight: bold;
    color: $footer-title;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;

    &:after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 30px;
      height: 2px;
      background-color: $accent-color;
    }
  }

  // 社交媒体区域
  .social-section {
    flex: 0 0 100%;
    margin-bottom: 35px;
    padding-bottom: 30px;
    border-bottom: 1px solid $footer-border;

    .social-links {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
    }

    .social-link {
      display: flex;
      align-items: center;
      gap: 8px;
      color: $footer-link;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
      position: relative;
      transition: color 0.3s ease;

      .social-item {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      &:hover {
        color: $footer-link-hover;
      }

      .iconfont {
        font-size: 22px;
      }
    }

    // 微信二维码弹窗
    .wx-code-box {
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%);
      padding-bottom: 15px;
      z-index: 100;

      .ewm {
        width: 150px;
        border: 1px solid #444;
        background-color: #333;
        padding: 10px 8px;
        border-radius: 4px;

        .tip {
          font-size: 12px;
          color: #aaa;
          margin-top: 6px;
          text-align: center;
        }

        .pictrue {
          width: 130px;
          height: 130px;
          vertical-align: middle;
          position: relative;

          img {
            width: 100%;
            height: 100%;
          }

          .arrow {
            position: absolute;
            bottom: -16px;
            left: 50%;
            margin-left: -8px;
            width: 0px;
            height: 0px;
            border: 8px solid transparent;
            border-top-color: #333;

            &:before {
              display: none;
            }
          }
        }
      }
    }
  }

  // 信息链接区域
  .info-section {
    flex: 1;
    min-width: 140px;
    margin-right: 30px;

    .footer-links {
      list-style: none;
      padding: 0;
      margin: 0;

      li {
        margin-bottom: 10px;

        a {
          color: $footer-link;
          text-decoration: none;
          font-size: 13px;
          font-weight: bold;
          transition: color 0.3s ease;

          &:hover {
            color: $footer-link-hover;
          }
        }
      }
    }
  }

  // 联系区域
  .contact-section {
    flex: 1;
    min-width: 200px;

    .contact-info {
      list-style: none;
      padding: 0;
      margin: 0;

      li {
        margin-bottom: 10px;
        display: flex;
        align-items: flex-start;
        gap: 6px;
        font-size: 13px;
        font-weight: bold;
        color: $footer-link;

        .iconfont {
          font-size: 16px;
          color: $footer-text;
          margin-top: 2px;
        }

        a {
          color: $footer-link;
          text-decoration: none;
          transition: color 0.3s ease;

          &:hover {
            color: $footer-link-hover;
          }
        }
      }
    }
  }

  // 友情链接区域
  .links-section {
    flex: 0 0 100%;
    margin-top: 35px;
    padding-top: 30px;
    border-top: 1px solid $footer-border;

    .friend-links {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 20px;

      a {
        color: $footer-link;
        text-decoration: none;
        font-size: 13px;
        font-weight: bold;
        transition: color 0.3s ease;

        &:hover {
          color: $footer-link-hover;
        }
      }
    }
  }
}

// 版权信息区域
.copyright-wrapper {
  background-color: $copyright-bg;
  padding: 20px 0;
  text-align: center;
  font-family: $font-family;

  .copyright-info {
    font-size: 13px;
    font-weight: bold;
    color: $copyright-text;
    margin-bottom: 6px;

    a {
      color: $copyright-text;
      text-decoration: none;

      &:hover {
        color: $footer-link-hover;
      }
    }
  }

  .record-info {
    font-size: 12px;
    font-weight: bold;
    color: $copyright-text;

    .line {
      margin: 0 8px;
      color: $footer-border;
    }

    .num {
      color: $copyright-text;
      text-decoration: none;

      &:hover {
        color: $footer-link-hover;
      }
    }

    .beian {
      display: inline-block;
      width: 17px;
      height: 18px;
      margin: 0 4px 0 0;
      vertical-align: middle;
    }
  }
}
</style>
