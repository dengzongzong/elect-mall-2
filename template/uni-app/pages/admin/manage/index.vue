<template>
  <view class="pagebox">
    <!-- #ifdef MP || APP-PLUS -->
    <NavBar
      titleText="工作台"
      :iconColor="iconColor"
      :textColor="iconColor"
      :isScrolling="isScrolling"
      showBack
    ></NavBar>
    <!-- #endif -->
    <view class="headerBg">
      <view :style="{ height: `${getHeight.barTop}px` }"></view>
      <view :style="{ height: `${getHeight.barHeight}px` }"></view>
      <view class="inner"></view>
    </view>
    <view class="page-content">
      <view class="header acea-row row-middle">
        <image :src="avatar" class="avatar"></image>
        <view class="text-box">
          <view class="flex-y-center">
            <text class="fs-32 fw-500 lh-44rpx pr-16">{{ nickname }}</text>
          </view>
          <view class="pt-4 fs-22 lh-30rpx">{{ phone }}</view>
        </view>
        <!-- #ifdef MP-WEIXIN || APP-PLUS -->
        <navigator
          url="/pages/admin/order_cancellation/index"
          hover-class="none"
        >
          <text class="iconfont icon-ic_Scan fs-40 text--w111-fff"></text>
        </navigator>
        <!-- #endif -->
      </view>
      <view class="today">
        <view class="title-box">
          <navigator
            class="link"
            url="/pages/admin/order/index"
            hover-class="none"
          >
            今日销售额(元)<text class="iconfont icon-ic_rightarrow"></text>
          </navigator>
          <view class="money">{{ todayOrderPrice }}</view>
        </view>
        <view class="acea-row">
          <view class="item">
            <view class="num">{{ todayOrderCount }}</view>
            <view class="">今日订单数</view>
          </view>
          <view class="item">
            <view class="num">{{ todayOrderUserCount }}</view>
            <view class="">今日支付人数</view>
          </view>
          <view class="item">
            <view class="num">{{ todayVisitCount }}</view>
            <view class="">今日浏览量</view>
          </view>
        </view>
      </view>
      <view class="goods grid-column-4 rd-24rpx bg--w111-fff mt-20">
        <navigator
          url="/pages/admin/orderList/index?types=1"
          hover-class="none"
          class="w-full flex-col flex-center pt-28 pb-26rpx fs-26"
        >
          <view class="img-box">
            <view class="img">
              <view v-if="unDeliveryOrderCount" class="num">{{
                unDeliveryOrderCount > 99 ? "99+" : unDeliveryOrderCount
              }}</view>
              <image
                :src="imgHost + '/statics/images/admin-work-order1.png'"
                mode=""
              ></image>
            </view>
          </view>
          <view class="pt-12">待发货</view>
        </navigator>
        <navigator
          url="/pages/admin/refund_order_list/index"
          hover-class="none"
          class="w-full flex-col flex-center pt-28 pb-26rpx fs-26"
        >
          <view class="img-box">
            <view class="img">
              <view v-if="refundingCount" class="num">{{
                refundingCount > 99 ? "99+" : refundingCount
              }}</view>
              <image
                :src="imgHost + '/statics/images/admin-work-order2.png'"
                mode=""
              ></image>
            </view>
          </view>
          <view class="pt-12">待售后</view>
        </navigator>
        <navigator
          url="/pages/admin/goods/index?type=4"
          hover-class="none"
          class="w-full flex-col flex-center pt-28 pb-26rpx fs-26"
        >
          <view class="img-box">
            <view class="img">
              <view v-if="outOfStock" class="num">{{
                outOfStock > 99 ? "99+" : outOfStock
              }}</view>
              <image
                :src="imgHost + '/statics/images/admin-work-order3.png'"
                mode=""
              ></image>
            </view>
          </view>
          <view class="pt-12">待补货</view>
        </navigator>
        <navigator
          url="/pages/admin/goods/index?type=5"
          hover-class="none"
          class="w-full flex-col flex-center pt-28 pb-26rpx fs-26"
        >
          <view class="img-box">
            <view class="img">
              <view v-if="policeForce" class="num">{{
                policeForce > 99 ? "99+" : policeForce
              }}</view>
              <image
                :src="imgHost + '/statics/images/admin-work-order4.png'"
                mode=""
              ></image>
            </view>
          </view>
          <view class="pt-12">库存预警</view>
        </navigator>
      </view>
      <view class="pt-34 pb-40 rd-24rpx mt-20 bg--w111-fff">
        <view class="pl-28 fs-30 fw-500 lh-44rpx">店铺管理</view>
        <view class="grid-column-4">
          <navigator
            class="w-full py-24 flex-col flex-center"
            url="/pages/admin/goods/index"
            hover-class="none"
          >
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu1.png'"
              mode=""
            ></image>
            <view class="pt-20">商品管理</view>
          </navigator>
          <navigator
            class="w-full py-24 flex-col flex-center"
            url="/pages/admin/goods/addGoods"
            hover-class="none"
          >
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu1.png'"
              mode=""
            ></image>
            <view class="pt-20">添加商品</view>
          </navigator>
          <navigator
            class="w-full py-24 flex-col flex-center"
            url="/pages/admin/orderList/index"
            hover-class="none"
          >
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu2.png'"
              mode=""
            ></image>
            <view class="pt-20">订单管理</view>
          </navigator>
          <!-- #ifdef MP-WEIXIN || APP-PLUS -->
          <navigator
            class="w-full py-24 flex-col flex-center"
            url="/pages/admin/order_cancellation/index"
            hover-class="none"
          >
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu3.png'"
              mode=""
            ></image>
            <view class="pt-20">扫码核销</view>
          </navigator>
          <!-- #endif -->
          <navigator
            class="w-full py-24 flex-col flex-center"
            url="/pages/admin/refund_order_list/index"
            hover-class="none"
          >
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu4.png'"
              mode=""
            ></image>
            <view class="pt-20">售后维权</view>
          </navigator>
          <navigator
            class="w-full py-24 flex-col flex-center"
            url="/pages/admin/user/list"
            hover-class="none"
          >
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu5.png'"
              mode=""
            ></image>
            <view class="pt-20">用户管理</view>
          </navigator>
          <view class="w-full py-24 flex-col flex-center" @tap="toChat">
            <image
              class="w-48 h-48"
              src="../static/footer5-1.png"
              mode=""
            ></image>
            <view class="pt-20">客服聊天</view>
          </view>
          <!-- #ifdef MP-WEIXIN || APP-PLUS -->
          <view class="w-full py-24 flex-col flex-center" @tap="toGoods">
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu3.png'"
              mode=""
            ></image>
            <view class="pt-20">扫描商品</view>
          </view>
          <!-- #endif -->
          <!-- #ifdef H5 -->
          <view
            class="w-full py-24 flex-col flex-center"
            v-if="isWechat"
            @tap="toGoods"
          >
            <image
              class="w-48 h-48"
              :src="imgHost + '/statics/images/admin-work-menu3.png'"
              mode=""
            ></image>
            <view class="pt-20">扫描商品</view>
          </view>
          <!-- #endif -->
        </view>
      </view>
    </view>
    <footerPage></footerPage>
  </view>
</template>

<script>
// #ifdef MP || APP-PLUS
import NavBar from "@/components/NavBar.vue";
// #endif
import footerPage from "../components/footerPage/index.vue";
import { HTTP_REQUEST_URL } from "@/config/app";
import {
  deliveryInfo,
  getOrderStaging,
  getManageStatistics,
} from "@/api/admin.js";
import { getUserInfo } from "@/api/user.js";

export default {
  components: {
    // #ifdef MP || APP-PLUS
    NavBar,
    // #endif
    footerPage,
  },
  data() {
    return {
      imgHost: HTTP_REQUEST_URL,
      // #ifdef MP || APP-PLUS
      iconColor: "#FFFFFF",
      isScrolling: false,
      // #endif
      avatar: "",
      nickname: "",
      phone: "",
      outOfStock: 0, // 待补货
      policeForce: 0, // 库存预警
      unDeliveryOrderCount: 0, // 待发货
      refundingCount: 0, // 待售后
      todayOrderPrice: 0, // 今日销售额
      todayOrderCount: 0, // 今日订单数
      todayOrderUserCount: 0, // 今日支付人数
      todayVisitCount: 0, // 今日浏览量
      getHeight: this.$util.getWXStatusHeight(),
      identity: 0,
      // #ifdef H5
      isWechat: this.$wechat.isWeixin(),
      // #endif
    };
  },
  onLoad() {
    this.getUserInfo();
    this.manageStatistics();
  },
  methods: {
    getUserInfo() {
      getUserInfo().then((res) => {
        const { avatar, nickname, phone } = res.data;
        this.avatar = avatar;
        this.nickname = nickname;
        this.phone = phone;
        this.identity = res.data.identity;
        this.$store.commit("UPDATE_USERINFO", res.data);
      });
    },
    // 商家管理统计
    manageStatistics() {
      getManageStatistics()
        .then((res) => {
          const {
            todayOrderCount,
            todayOrderUserCount,
            todayOrderPrice,
            todayVisitCount,
            outOfStock,
            policeForce,
            unDeliveryOrderCount,
            refundingCount,
          } = res.data;
          this.todayOrderCount = todayOrderCount;
          this.todayOrderUserCount = todayOrderUserCount;
          this.todayOrderPrice = todayOrderPrice;
          this.todayVisitCount = todayVisitCount;
          this.outOfStock = outOfStock;
          this.policeForce = policeForce;
          this.unDeliveryOrderCount = unDeliveryOrderCount;
          this.refundingCount = refundingCount;
        })
        .catch((err) => {
          return this.$util.Tips({
            title: err,
          });
        });
    },
    toChat() {
      // #ifdef H5
      this.$util.JumpPath(`${HTTP_REQUEST_URL}/kefu`);
      // #endif
      // #ifdef MP-WEIXIN
      uni.navigateTo({
        url: `/pages/annex/web_view/index?url=${HTTP_REQUEST_URL}/kefu`,
      });
      // #endif
    },
    toGoods() {
      var self = this;
      // #ifdef MP || APP
      uni.scanCode({
        scanType: ["barCode"],
        success(res) {
          uni.navigateTo({
            url: `/pages/admin/goods/index?keyword=${res.result}`,
          });
        },
        fail(res) {
          console.log(res);
        },
      });
      // #endif
      //#ifdef H5
      this.$wechat
        .wechatEvevt("scanQRCode", {
          needResult: 1,
          scanType: ["barCode"],
        })
        .then((res) => {
          let result = res.resultStr;
          uni.navigateTo({
            url: `/pages/admin/goods/index?keyword=${result}`,
          });
        });
      //#endif
    },
  },
};
</script>

<style lang="scss" scoped>
.pagebox {
  position: relative;
  overflow-x: hidden;
}

.headerBg {
  position: absolute;
  top: 0;
  left: -25%;
  width: 150%;
  border-bottom-right-radius: 100%;
  border-bottom-left-radius: 100%;
  background: linear-gradient(
    270deg,
    $gradient-primary-admin 0%,
    $primary-admin 100%
  );

  .inner {
    height: 356rpx;
  }
}

.page-content {
  position: relative;
  padding: 0 20rpx;

  .header {
    padding: 22rpx 20rpx 32rpx 12rpx;

    .avatar {
      width: 80rpx;
      height: 80rpx;
      border-radius: 50%;
    }

    .text-box {
      flex: 1;
      padding-left: 16rpx;
      color: #ffffff;
    }
  }

  .today {
    border-radius: 24rpx;
    background: #ffffff;

    .title-box {
      padding: 40rpx 0 0 40rpx;
    }

    .link {
      display: inline-block;
      vertical-align: middle;
      font-size: 24rpx;
      line-height: 34rpx;
      color: #999999;
    }

    .iconfont {
      margin-left: 4rpx;
      font-size: 24rpx;
    }

    .money {
      margin-top: 16rpx;
      font-family: SemiBold;
      font-size: 56rpx;
      line-height: 56rpx;
      color: #ff7e00;
    }

    .item {
      flex: 1;
      padding: 54rpx 0 38rpx;
      text-align: center;
      font-size: 24rpx;
      line-height: 34rpx;
      color: #999999;
    }

    .num {
      margin-bottom: 12rpx;
      font-family: SemiBold;
      font-size: 36rpx;
      line-height: 36rpx;
      color: #333333;
    }
  }

  .goods {
    .img-box {
      position: relative;
      width: 76rpx;
      height: 76rpx;
      border-radius: 50%;
      margin: 0 auto 12rpx;
    }

    .img {
      width: 100%;
      height: 100%;
    }

    .num {
      position: absolute;
      top: -6rpx;
      left: 58rpx;
      z-index: 2;
      min-width: 24rpx;
      height: 24rpx;
      padding: 0 8rpx;
      border-radius: 12rpx;
      background: #ff7e00;
      font-weight: 500;
      font-size: 18rpx;
      line-height: 24rpx;
      color: #ffffff;
    }

    image {
      width: 100%;
      height: 100%;
    }
  }
}
.b-f {
  border: 1rpx solid #fff;
}
</style>
