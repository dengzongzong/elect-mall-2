<template>
  <div>
    <div class="footer">
      <div class="wrapper_1200">
        <ul class="acea-row row-around row-middle">
          <li class="acea-row row-middle">
            <div class="picture">
              <span class="iconfont icon-pinzhong"></span>
            </div>
            <div>品种齐全，购物轻松</div>
          </li>
          <li class="acea-row row-middle">
            <div class="picture"><span class="iconfont icon-zhifa"></span></div>
            <div>多仓直发，极速配送</div>
          </li>
          <li class="acea-row row-middle">
            <div class="picture">
              <span class="iconfont icon-hanghuo"></span>
            </div>
            <div>正品行货，精致服务</div>
          </li>
          <li class="acea-row row-middle">
            <div class="picture"><span class="iconfont icon-dijia"></span></div>
            <div>天天低价，畅选无忧</div>
          </li>
        </ul>
        <div
          class="links"
          v-if="companyInfo.pc_home_links && companyInfo.pc_home_links.length"
        >
          <span>友情链接：</span>
          <a
            v-for="(item, index) in companyInfo.pc_home_links"
            :href="item.url"
            :key="index"
            target="_blank"
            >{{ item.title }}</a
          >
        </div>
        <div class="recordNum">
          <div>
            <span>联系电话：{{ companyInfo.contact_number }}</span>
            <span class="line">|</span>

            <span class="address">地址：{{ companyInfo.company_address }}</span>
          </div>
          <div class="record">
            <span class="foot-box" v-if="companyInfo.copyright">
              {{ companyInfo.copyright }}
            </span>
            <span class="foot-box" v-else>
              <a href="https://www.crmeb.com" target="_blank"
                >Copyright ©2025 CRMEB. All Rights</a
              >
            </span>
            <span class="line" v-if="companyInfo.record_No">|</span>
            <span v-if="companyInfo.record_No">
              <a :href="companyInfo.icp_url" target="_blank" class="num">{{
                companyInfo.record_No
              }}</a>
            </span>
            <span class="line" v-if="companyInfo.network_security">|</span>
            <span v-if="companyInfo.network_security">
              <img
                class="beian"
                src="~/assets/images/beian.png"
                alt=""
                srcset=""
              />
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
    </div>
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
.footer {
  margin-top: 50px;
  background-color: #f2f2f2;
  .wrapper_1200 {
    background-color: #f2f2f2;
  }
  ul {
    height: 104px;
    border-bottom: 1px solid #e3e3e3;
    li {
      .picture {
        width: 40px;
        height: 40px;
        border: 1px solid #707070;
        border-radius: 50%;
        text-align: center;
        line-height: 40px;
        margin-right: 14px;
        .iconfont {
          font-size: 23px;
          color: #707070;
        }
      }
    }
  }
  .links {
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid #e3e3e3;
    padding: 17px 0;
    font-weight: 400;
    font-size: 12px;
    color: #282828;
    line-height: 14px;
    a {
      color: #282828;
      margin-right: 10px;
    }
  }
  .line {
    margin: 0 10px;
  }
  .recordNum {
    text-align: center;
    padding: 30px 0 48px 0;
    .address {
      margin-left: 40px;
    }
    .record {
      margin-top: 6px;
      line-height: 15px;
      .num {
        &:hover {
          color: #e93323;
        }
      }
      .beian {
        display: inline-block;
        width: 17px;
        height: 18px;
        margin: 0 4px 0 4px;
        vertical-align: middle;
      }
    }
  }
}
</style>
