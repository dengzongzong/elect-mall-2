<template>
  <view :style="colorStyle" class="order-addcart">
    <view class="guarantee-bar">
      <view class="guarantee-item"
        ><text class="iconfont icon-xuanzhong"></text
        ><text>{{ $t(`100%正品保证`) }}</text></view
      >
      <view class="guarantee-item"
        ><text class="iconfont icon-xuanzhong"></text
        ><text>{{ $t(`所有商品精挑细选`) }}</text></view
      >
      <view class="guarantee-item"
        ><text class="iconfont icon-xuanzhong"></text
        ><text>{{ $t(`售后无忧`) }}</text></view
      >
    </view>
    <view class="main-content" v-if="canShow">
      <view class="nav-bar">
        <view class="nav-left"
          >{{ $t(`购物数量`) }}<text class="count">{{ cartCount }}</text></view
        >
        <view
          v-if="cartList.valid.length || cartList.invalid.length"
          class="nav-right"
          @click="manage"
          >{{ footerswitch ? $t(`管理`) : $t(`取消`) }}</view
        >
      </view>
      <view
        v-if="cartList.valid.length || cartList.invalid.length"
        class="goods-wrapper"
      >
        <view class="valid-list">
          <checkbox-group @change="checkboxChange">
            <view
              v-for="(item, index) in cartList.valid"
              :key="index"
              class="goods-item"
            >
              <checkbox
                :value="item.id.toString()"
                :checked="item.checked"
                :disabled="!item.attrStatus && footerswitch"
              />
              <navigator
                :url="'/pages/goods_details/index?id=' + item.product_id"
                hover-class="none"
                class="goods-main"
              >
                <view class="goods-img"
                  ><image
                    v-if="item.productInfo.attrInfo"
                    :src="item.productInfo.attrInfo.image"
                    mode="aspectFill"
                  ></image
                  ><image
                    v-else
                    :src="item.productInfo.image"
                    mode="aspectFill"
                  ></image
                ></view>
                <view class="goods-info">
                  <view
                    class="name line2"
                    :class="{ gray: !item.attrStatus }"
                    >{{ item.productInfo.store_name }}</view
                  >
                  <view class="sku line1" v-if="item.productInfo.attrInfo"
                    >{{ $t(`属性`) }}：{{ item.productInfo.attrInfo.suk }}</view
                  >
                  <view class="price" v-if="item.attrStatus"
                    >{{ $t(`￥`) }}{{ item.truePrice }}</view
                  >
                  <view class="reselect" v-else
                    ><text class="tip">{{ $t(`请重新选择商品规格`) }}</text
                    ><view class="btn" @click.stop="reElection(item)">{{
                      $t(`重选`)
                    }}</view></view
                  >
                </view>
                <view v-if="item.attrStatus" class="qty-ctrl">
                  <view
                    class="qty-btn reduce"
                    :class="{ off: item.numSub }"
                    @click.stop="subCart(index)"
                    >-</view
                  >
                  <view class="qty-num"
                    ><input
                      type="number"
                      v-model="item.cart_num"
                      @click.stop
                      @input="iptCartNum(index)"
                      @blur="blurInput(index)"
                  /></view>
                  <view
                    class="qty-btn plus"
                    :class="{ off: item.numAdd && !disabledChangeNumber }"
                    @click.stop="addCart(index)"
                    >+</view
                  >
                </view>
              </navigator>
            </view>
          </checkbox-group>
        </view>
        <view class="invalid-section" v-if="cartList.invalid.length">
          <view class="invalid-header">
            <view class="header-left" @click="goodsOpen"
              ><text
                class="iconfont"
                :class="goodsHidden ? 'icon-xiangxia' : 'icon-xiangshang'"
              ></text
              ><text>{{ $t(`失效商品`) }}</text></view
            >
            <view class="header-right" @click="unsetCart"
              ><text class="iconfont icon-shanchu1"></text
              ><text>{{ $t(`清空`) }}</text></view
            >
          </view>
          <view class="invalid-list" :class="{ hide: goodsHidden }">
            <view
              v-for="(item, index) in cartList.invalid"
              :key="index"
              class="invalid-item"
            >
              <view class="invalid-tag">{{ $t(`失效`) }}</view>
              <view class="goods-img"
                ><image
                  v-if="item.productInfo.attrInfo"
                  :src="item.productInfo.attrInfo.image"
                  mode="aspectFill"
                ></image
                ><image
                  v-else
                  :src="item.productInfo.image"
                  mode="aspectFill"
                ></image
              ></view>
              <view class="goods-info"
                ><view class="name line1">{{
                  item.productInfo.store_name
                }}</view
                ><view class="sku line1" v-if="item.productInfo.attrInfo"
                  >{{ $t(`属性`) }}：{{ item.productInfo.attrInfo.suk }}</view
                ><view class="tip-text">{{ $t(`该商品已失效`) }}</view></view
              >
            </view>
          </view>
        </view>
        <view class="load-more" v-if="cartList.invalid.length && loadend"
          ><text
            class="loading iconfont icon-jiazai"
            :hidden="!loadingInvalid"
          ></text
          ><text>{{ loadTitleInvalid }}</text></view
        >
      </view>
      <view class="empty-cart" v-else>
        <view class="empty-box"
          ><image
            :src="imgHost + '/statics/images/no-thing.png'"
            mode="aspectFit"
          ></image
          ><view class="tips">{{ $t(`暂无商品`) }}</view></view
        >
        <recommend
          v-if="hostProduct.length"
          :hostProduct="hostProduct"
        ></recommend>
      </view>
      <view class="bottom-space" :style="medHeight"></view>
      <view
        class="footer-bar"
        v-if="cartList.valid.length"
        :class="{ active: is_diy && is_diy_set }"
        :style="componentStyle"
      >
        <view class="bar-left"
          ><checkbox-group @change="checkboxAllChange"
            ><checkbox value="all" :checked="!!isAllSelect" /><text
              >{{ $t(`全选`) }}({{ selectValue.length }})</text
            ></checkbox-group
          ></view
        >
        <view class="bar-right" v-if="footerswitch"
          ><text class="total">{{ $t(`￥`) }}{{ selectCountPrice }}</text>
          <form @submit="subOrder">
            <button class="submit-btn" formType="submit">
              {{ $t(`立即下单`) }}
            </button>
          </form></view
        >
        <view class="bar-actions" v-else
          ><form @submit="subCollect">
            <button class="action-btn" formType="submit">
              {{ $t(`收藏`) }}
            </button>
          </form>
          <form @submit="subDel">
            <button class="action-btn danger" formType="submit">
              {{ $t(`删除`) }}
            </button>
          </form></view
        >
      </view>
    </view>
    <productWindow
      :attr="attr"
      :isShow="1"
      :iSplus="1"
      :iScart="1"
      @myevent="onMyEvent"
      @ChangeAttr="ChangeAttr"
      @ChangeCartNum="ChangeCartNum"
      @attrVal="attrVal"
      @iptCartNum="iptCartNum"
      @goCat="reGoCat"
      id="product-window"
    ></productWindow>
    <view class="footer-hold"></view>
    <pageFooter @newDataStatus="newDataStatus"></pageFooter>
  </view>
</template>

<script>
import {
  getCartList,
  getCartCounts,
  changeCartNum,
  cartDel,
  getResetCart,
} from "@/api/order.js";
import { getProductHot, collectAll, getProductDetail } from "@/api/store.js";
import { toLogin } from "@/libs/login.js";
import { mapGetters } from "vuex";
import recommend from "@/components/recommend";
import productWindow from "@/components/productWindow";
import pageFooter from "@/components/pageFooter/index.vue";
import colors from "@/mixins/color";
import { HTTP_REQUEST_URL, DEBOUNCETIME } from "@/config/app";
import { Throttle } from "@/utils/validate.js";

export default {
  name: "OrderAddCart",
  components: { pageFooter, recommend, productWindow },
  mixins: [colors],
  data() {
    return {
      imgHost: HTTP_REQUEST_URL,
      is_diy: uni.getStorageSync("is_diy"),
      canShow: false,
      cartCount: 0,
      goodsHidden: true,
      footerswitch: true,
      hostProduct: [],
      cartList: { valid: [], invalid: [] },
      isAllSelect: false,
      selectValue: [],
      selectCountPrice: 0.0,
      isAuto: false,
      isShowAuth: false,
      hotScroll: false,
      hotPage: 1,
      hotLimit: 10,
      loading: false,
      loadend: false,
      loadTitle: this.$t(`我也是有底线的`),
      page: 1,
      limit: 20,
      loadingInvalid: false,
      loadendInvalid: false,
      loadTitleInvalid: this.$t(`加载更多`),
      pageInvalid: 1,
      limitInvalid: 20,
      attr: { cartAttr: false, productAttr: [], productSelect: {} },
      productValue: [],
      storeInfo: {},
      attrValue: "",
      attrTxt: this.$t(`请选择`),
      cartId: 0,
      product_id: 0,
      sysHeight: 0,
      newData: {},
      activeRouter: "",
      is_diy_set: false,
      adding: false,
      disabledChangeNumber: false,
      isFooter: false,
      btmNum: 0,
      navConfig: 0,
      pdHeight: 0,
    };
  },
  computed: {
    ...mapGetters(["isLogin"]),
    componentStyle() {
      if (this.btmNum && this.navConfig)
        return { bottom: `${this.btmNum * 2 + 104}rpx` };
      return {};
    },
    medHeight() {
      const s = { backgroundColor: "#f5f5f5", height: "120rpx" };
      if (this.btmNum) s.height = `${this.btmNum * 2 + 120}rpx`;
      return s;
    },
  },
  onLoad() {
    uni.hideTabBar();
    const routes = getCurrentPages();
    this.activeRouter = "/" + routes[routes.length - 1].route;
  },
  onShow() {
    if (!this.isLogin) toLogin();
    this.canShow = false;
    if (this.isLogin) {
      this.resetData();
      this.getHostProduct();
      this.loadend = false;
      this.page = 1;
      this.cartList.valid = [];
      this.getCartList(1);
      this.loadendInvalid = false;
      this.pageInvalid = 1;
      this.cartList.invalid = [];
      this.getInvalidList();
      this.goodsHidden = true;
      this.footerswitch = true;
      this.cartCount = 0;
      this.isShowAuth = false;
    } else {
      this.hotPage = 1;
      this.hostProduct = [];
      this.hotScroll = false;
      this.getHostProduct();
      this.canShow = true;
    }
  },
  methods: {
    resetData() {
      this.hostProduct = [];
      this.hotScroll = false;
      this.hotPage = 1;
      this.hotLimit = 10;
      this.cartList = { valid: [], invalid: [] };
      this.isAllSelect = false;
      this.selectValue = [];
      this.selectCountPrice = 0.0;
    },
    authColse(e) {
      this.isShowAuth = e;
    },
    newDataStatus(val, num, btmNum, navConfig) {
      this.isFooter = !!val;
      this.pdHeight = num;
      this.btmNum = btmNum;
      this.navConfig = navConfig;
    },
    reGoCat() {
      const productSelect = this.productValue[this.attrValue];
      if (this.attr.productAttr.length && productSelect === undefined)
        return this.$util.Tips({ title: this.$t(`产品库存不足，请选择其它`) });
      const q = {
        id: this.cartId,
        product_id: this.product_id,
        num: this.attr.productSelect.cart_num,
        unique: this.attr.productSelect?.unique || "",
      };
      getResetCart(q)
        .then(() => {
          this.attr.cartAttr = false;
          this.$util.Tips({
            title: this.$t(`添加购物车成功`),
            success: () => {
              this.loadend = false;
              this.page = 1;
              this.cartList.valid = [];
              this.getCartList();
              this.getCartNum();
            },
          });
        })
        .catch((res) => {
          this.$util.Tips({ title: res.msg });
        });
    },
    onMyEvent() {
      this.$set(this.attr, "cartAttr", false);
    },
    reElection(item) {
      this.getGoodsDetails(item);
    },
    getGoodsDetails(item) {
      uni.showLoading({ title: this.$t(`加载中`), mask: true });
      this.cartId = item.id;
      this.product_id = item.product_id;
      getProductDetail(item.product_id)
        .then((res) => {
          uni.hideLoading();
          this.attr.cartAttr = true;
          this.storeInfo = res.data.storeInfo;
          this.$set(this.attr, "productAttr", res.data.productAttr);
          this.$set(this, "productValue", res.data.productValue);
          this.DefaultSelect();
        })
        .catch(() => {
          uni.hideLoading();
        });
    },
    ChangeAttr(res) {
      const productSelect = this.productValue[res];
      if (productSelect && productSelect.stock > 0) {
        this.$set(this.attr.productSelect, "image", productSelect.image);
        this.$set(this.attr.productSelect, "price", productSelect.price);
        this.$set(this.attr.productSelect, "stock", productSelect.stock);
        this.$set(this.attr.productSelect, "unique", productSelect.unique);
        this.$set(this.attr.productSelect, "cart_num", 1);
        this.$set(this, "attrValue", res);
        this.$set(this, "attrTxt", this.$t(`已选择`));
      } else {
        this.$set(this.attr.productSelect, "image", this.storeInfo.image);
        this.$set(this.attr.productSelect, "price", this.storeInfo.price);
        this.$set(this.attr.productSelect, "stock", 0);
        this.$set(this.attr.productSelect, "unique", "");
        this.$set(this.attr.productSelect, "cart_num", 0);
        this.$set(this, "attrValue", "");
        this.$set(this, "attrTxt", this.$t(`请选择`));
      }
    },
    DefaultSelect() {
      const productAttr = this.attr.productAttr;
      let value = [];
      for (const key in this.productValue) {
        if (this.productValue[key].stock > 0) {
          value = productAttr.length ? key.split(",") : [];
          break;
        }
      }
      for (let i = 0; i < productAttr.length; i++)
        this.$set(productAttr[i], "index", value[i]);
      const productSelect = this.productValue[value.sort().join(",")];
      if (productSelect && productAttr.length)
        this.initSelect(
          this.storeInfo.store_name,
          productSelect.image,
          productSelect.price,
          productSelect.stock,
          productSelect.unique,
          1,
          value.sort().join(",")
        );
      else if (!productSelect && productAttr.length)
        this.initSelect(
          this.storeInfo.store_name,
          this.storeInfo.image,
          this.storeInfo.price,
          0,
          "",
          0,
          ""
        );
      else if (!productSelect && !productAttr.length)
        this.initSelect(
          this.storeInfo.store_name,
          this.storeInfo.image,
          this.storeInfo.price,
          this.storeInfo.stock,
          this.storeInfo.unique || "",
          1,
          ""
        );
    },
    initSelect(name, image, price, stock, unique, num, attrValue) {
      this.$set(this.attr.productSelect, "store_name", name);
      this.$set(this.attr.productSelect, "image", image);
      this.$set(this.attr.productSelect, "price", price);
      this.$set(this.attr.productSelect, "stock", stock);
      this.$set(this.attr.productSelect, "unique", unique);
      this.$set(this.attr.productSelect, "cart_num", num);
      this.$set(this, "attrValue", attrValue);
      this.$set(
        this,
        "attrTxt",
        attrValue ? this.$t(`已选择`) : this.$t(`请选择`)
      );
    },
    attrVal(val) {
      this.$set(
        this.attr.productAttr[val.indexw],
        "index",
        this.attr.productAttr[val.indexw].attr_values[val.indexn]
      );
    },
    ChangeCartNum(changeValue) {
      let productSelect = this.productValue[this.attrValue];
      if (productSelect === undefined && !this.attr.productAttr.length)
        productSelect = this.attr.productSelect;
      if (productSelect === undefined) return;
      const stock = productSelect.stock || 0;
      const num = this.attr.productSelect;
      if (changeValue) {
        num.cart_num++;
        if (num.cart_num > stock)
          this.$set(this.attr.productSelect, "cart_num", stock || 1);
      } else {
        num.cart_num--;
        if (num.cart_num < 1) this.$set(this.attr.productSelect, "cart_num", 1);
      }
    },
    subDel() {
      if (this.selectValue.length > 0)
        cartDel(this.selectValue).then(() => {
          this.loadend = false;
          this.page = 1;
          this.cartList.valid = [];
          this.getCartList();
          this.getCartNum();
        });
      else this.$util.Tips({ title: this.$t(`请选择产品`) });
    },
    subCollect() {
      if (this.selectValue.length > 0) {
        const ids = this.cartList.valid
          .filter((i) => this.selectValue.includes(i.id.toString()))
          .map((i) => i.product_id)
          .join(",");
        collectAll(ids)
          .then((res) => {
            this.$util.Tips({ title: res.msg, icon: "success" });
          })
          .catch((err) => {
            this.$util.Tips({ title: err });
          });
      } else this.$util.Tips({ title: this.$t(`请选择产品`) });
    },
    subOrder() {
      if (this.selectValue.length > 0)
        uni.navigateTo({
          url:
            "/pages/goods/order_confirm/index?cartId=" +
            this.selectValue.join(","),
        });
      else this.$util.Tips({ title: this.$t(`请选择产品`) });
    },
    checkboxAllChange(e) {
      this.setAllSelectValue(e.detail.value.length > 0 ? 1 : 0);
    },
    setAllSelectValue(status) {
      const selectValue = [];
      const valid = this.cartList.valid;
      if (valid.length > 0) {
        const newValid = valid.map((item) => {
          if (status) {
            if (this.footerswitch) {
              if (item.attrStatus) {
                item.checked = true;
                selectValue.push(item.id.toString());
              } else item.checked = false;
            } else {
              item.checked = true;
              selectValue.push(item.id.toString());
            }
            this.isAllSelect = true;
          } else {
            item.checked = false;
            this.isAllSelect = false;
          }
          return item;
        });
        this.$set(this.cartList, "valid", newValid);
        this.selectValue = selectValue;
        this.switchSelect();
      }
    },
    checkboxChange(e) {
      const value = e.detail.value;
      const valid = this.cartList.valid;
      const arr1 = [],
        arr2 = [];
      let arr3 = [];
      const newValid = valid.map((item) => {
        if (value.includes(item.id.toString())) {
          if (this.footerswitch) {
            if (item.attrStatus) {
              item.checked = true;
              arr1.push(item);
            } else item.checked = false;
          } else {
            item.checked = true;
            arr1.push(item);
          }
        } else {
          item.checked = false;
          arr2.push(item);
        }
        return item;
      });
      if (this.footerswitch) arr3 = arr2.filter((item) => !item.attrStatus);
      this.$set(this.cartList, "valid", newValid);
      this.isAllSelect = newValid.length === arr1.length + arr3.length;
      this.selectValue = value;
      this.switchSelect();
    },
    switchSelect() {
      let selectCountPrice = 0.0;
      if (this.selectValue.length > 0) {
        for (const item of this.cartList.valid) {
          if (this.selectValue.includes(item.id.toString()))
            selectCountPrice = this.$util.$h.Add(
              selectCountPrice,
              this.$util.$h.Mul(item.cart_num, item.truePrice)
            );
        }
      }
      this.selectCountPrice = selectCountPrice;
    },
    iptCartNum(index) {
      const item = this.cartList.valid[index];
      if (item.cart_num) this.setCartNum(item.id, item.cart_num);
      if (!item.cart_num) item.cart_num = 1;
      this.switchSelect();
    },
    blurInput(index) {
      const item = this.cartList.valid[index];
      if (!item.cart_num) {
        item.cart_num = 1;
        this.$set(this.cartList, "valid", this.cartList.valid);
      }
    },
    subCart(index) {
      if (this.disabledChangeNumber) return;
      const item = this.cartList.valid[index];
      let cart_num = Number(item.cart_num) - 1;
      if (cart_num < 1 || cart_num < item.min_qty) {
        uni.showModal({
          title: "提示",
          content: "确定删除吗？",
          success: (res) => {
            if (res.confirm) {
              this.cartCount -= item.cart_num;
              cartDel([item.id]).then(() => {
                this.cartList.valid.splice(index, 1);
                this.$store.commit("indexData/setCartNum", this.cartCount);
              });
              this.selectValue = this.selectValue.filter(
                (i) => i !== item.id.toString()
              );
              this.switchSelect();
            }
          },
        });
      } else {
        item.cart_num = cart_num <= 1 ? 1 : cart_num;
        item.numSub = cart_num <= 1;
        item.numAdd = false;
        this.setCartNum(
          item.id,
          item.cart_num,
          () => {
            this.cartList.valid[index] = item;
            this.getCartNum();
            this.switchSelect();
          },
          () => {
            item.cart_num = Number(item.cart_num) + 1;
          }
        );
      }
    },
    addCart(index) {
      if (this.adding || this.disabledChangeNumber) return;
      const item = this.cartList.valid[index];
      item.cart_num = Number(item.cart_num) + 1;
      const productInfo = item.productInfo;
      if (
        productInfo.hasOwnProperty("attrInfo") &&
        item.cart_num >= productInfo.attrInfo.stock
      ) {
        item.cart_num = productInfo.attrInfo.stock;
        item.numAdd = true;
        item.numSub = false;
      } else {
        item.numAdd = false;
        item.numSub = false;
      }
      Throttle(
        this.setCartNum(
          item.id,
          item.cart_num,
          () => {
            this.cartList.valid[index] = item;
            this.getCartNum();
            this.switchSelect();
          },
          () => {
            item.cart_num = Number(item.cart_num) - 1;
          }
        ),
        3000
      );
    },
    setCartNum(cartId, cartNum, successCallback, errorCallback) {
      if (this.disabledChangeNumber) return;
      this.disabledChangeNumber = true;
      changeCartNum(cartId, cartNum)
        .then((res) => {
          successCallback?.(res.data);
        })
        .catch(() => {
          errorCallback?.();
        })
        .finally(() => {
          setTimeout(() => {
            this.disabledChangeNumber = false;
          }, DEBOUNCETIME);
        });
    },
    getCartNum() {
      getCartCounts().then((res) => {
        this.cartCount = res.data.count;
        this.adding = false;
        this.$store.commit(
          "indexData/setCartNum",
          res.data.count > 99 ? ".." : res.data.count
        );
        if (res.data.count > 0)
          wx.setTabBarBadge({ index: 2, text: res.data.count + "" });
        else wx.hideTabBarRedDot({ index: 2 });
      });
    },
    getCartData(data) {
      return new Promise((resolve, reject) => {
        getCartList(data)
          .then((res) => resolve(res.data))
          .catch((err) => {
            this.loading = false;
            this.canShow = true;
            this.$util.Tips({ title: err });
          });
      });
    },
    async getCartList(init) {
      uni.showLoading({ title: this.$t(`加载中`), mask: true });
      const data = { page: this.page, limit: this.limit, status: 1 };
      getCartCounts().then(async (c) => {
        this.cartCount = c.data.count;
        if (init) {
          this.adding = false;
          this.$store.commit(
            "indexData/setCartNum",
            c.data.count > 99 ? ".." : c.data.count
          );
          if (c.data.count > 0)
            wx.setTabBarBadge({ index: 2, text: c.data.count + "" });
          else wx.hideTabBarRedDot({ index: 2 });
        }
        for (let i = 0; i < Math.ceil(c.data.ids.length / this.limit); i++) {
          const cartList = await this.getCartData(data);
          data.page++;
          let validList = this.$util.SplitArray(
            cartList.valid,
            this.cartList.valid
          );
          const selectValue = [];
          if (validList.length > 0) {
            for (const item of validList) {
              item.numSub = item.cart_num == 1;
              const productInfo = item.productInfo;
              if (
                productInfo.hasOwnProperty("attrInfo") &&
                item.cart_num == productInfo.attrInfo.stock
              )
                item.numAdd = true;
              else if (item.cart_num == productInfo.stock) item.numAdd = true;
              else item.numAdd = false;
              if (item.attrStatus) {
                item.checked = true;
                selectValue.push(item.id.toString());
              } else item.checked = false;
            }
          }
          this.$set(this.cartList, "valid", validList);
          this.selectValue = selectValue;
          const newArr = validList.filter((item) => item.attrStatus);
          this.isAllSelect =
            newArr.length === selectValue.length && newArr.length;
          this.switchSelect();
        }
        this.loading = false;
        this.canShow = true;
        uni.hideLoading();
      });
    },
    getInvalidList() {
      if (this.loadendInvalid || this.loadingInvalid) return;
      const data = {
        page: this.pageInvalid,
        limit: this.limitInvalid,
        status: 0,
      };
      getCartList(data)
        .then((res) => {
          const { invalid } = res.data;
          const loadendInvalid = invalid.length < this.limitInvalid;
          const invalidList = this.$util.SplitArray(
            invalid,
            this.cartList.invalid
          );
          this.$set(this.cartList, "invalid", invalidList);
          this.loadendInvalid = loadendInvalid;
          this.loadTitleInvalid = loadendInvalid
            ? this.$t(`我也是有底线的`)
            : this.$t(`加载更多`);
          this.pageInvalid++;
          this.loadingInvalid = false;
        })
        .catch(() => {
          this.loadingInvalid = false;
          this.loadTitleInvalid = this.$t(`加载更多`);
        });
    },
    getHostProduct() {
      if (this.hotScroll) return;
      getProductHot(this.hotPage, this.hotLimit).then((res) => {
        this.hotPage++;
        this.hotScroll = res.data.length < this.hotLimit;
        this.hostProduct = this.hostProduct.concat(res.data);
      });
    },
    goodsOpen() {
      this.goodsHidden = !this.goodsHidden;
    },
    manage() {
      this.footerswitch = !this.footerswitch;
      const arr1 = [],
        arr2 = [];
      const newValid = this.cartList.valid.map((item) => {
        if (this.footerswitch) {
          if (item.attrStatus) {
            if (item.checked) arr1.push(item.id.toString());
          } else {
            item.checked = false;
            arr2.push(item);
          }
        } else {
          if (item.checked) arr1.push(item.id.toString());
        }
        return item;
      });
      this.cartList.valid = newValid;
      this.isAllSelect = this.footerswitch
        ? newValid.length === arr1.length + arr2.length
        : newValid.length === arr1.length;
      this.selectValue = arr1;
      this.switchSelect();
    },
    unsetCart() {
      const ids = this.cartList.invalid.map((item) => item.id);
      cartDel(ids)
        .then(() => {
          this.$util.Tips({ title: this.$t(`清除成功`) });
          this.$set(this.cartList, "invalid", []);
          this.getCartNum();
        })
        .catch(() => {});
    },
  },
  onReachBottom() {
    if (this.loadend) this.getInvalidList();
    if (this.cartList.valid.length === 0 && this.cartList.invalid.length === 0)
      this.getHostProduct();
  },
  onPageScroll() {
    uni.$emit("scroll");
  },
};
</script>

<style scoped lang="scss">
.order-addcart {
  min-height: 100vh;
  background: #f5f5f5;
}
.guarantee-bar {
  display: flex;
  justify-content: space-around;
  align-items: center;
  height: 76rpx;
  padding: 0 30rpx;
  font-size: 22rpx;
  color: #8c8c8c;
  background: #f5f5f5;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10;
  box-sizing: border-box;
  .iconfont {
    font-size: 25rpx;
    margin-right: 8rpx;
  }
}
.main-content {
  padding-top: 76rpx;
}
.nav-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 80rpx;
  padding: 0 30rpx;
  font-size: 28rpx;
  color: #282828;
  background: #fff;
  position: fixed;
  top: 76rpx;
  left: 0;
  right: 0;
  z-index: 10;
  box-sizing: border-box;
  .count {
    margin-left: 12rpx;
  }
  .nav-right {
    font-size: 26rpx;
    padding: 0 20rpx;
    height: 46rpx;
    line-height: 46rpx;
    border: 1rpx solid #a4a4a4;
    border-radius: 6rpx;
  }
}
.goods-wrapper {
  margin-top: 90rpx;
}
.valid-list {
  .goods-item {
    display: flex;
    align-items: center;
    padding: 25rpx 30rpx;
    background: #fff;
    margin-bottom: 15rpx;
    .goods-main {
      flex: 1;
      display: flex;
      align-items: flex-start;
      margin-left: 20rpx;
      position: relative;
    }
    .goods-img {
      width: 160rpx;
      height: 160rpx;
      flex-shrink: 0;
      border-radius: 6rpx;
      overflow: hidden;
      image {
        width: 100%;
        height: 100%;
      }
    }
    .goods-info {
      flex: 1;
      margin-left: 20rpx;
      font-size: 28rpx;
      color: #282828;
      .name {
        line-height: 1.4;
        &.gray {
          color: #999;
        }
      }
      .sku {
        font-size: 24rpx;
        color: #868686;
        margin-top: 16rpx;
      }
      .price {
        font-size: 32rpx;
        color: var(--view-theme);
        margin-top: 28rpx;
      }
      .reselect {
        margin-top: 20rpx;
        display: flex;
        align-items: center;
        justify-content: space-between;
        .tip {
          font-size: 24rpx;
          color: #999;
        }
        .btn {
          padding: 0 20rpx;
          height: 46rpx;
          line-height: 46rpx;
          background: var(--view-theme);
          color: #fff;
          border-radius: 23rpx;
          font-size: 26rpx;
        }
      }
    }
    .qty-ctrl {
      position: absolute;
      bottom: 0;
      right: 0;
      display: flex;
      align-items: center;
      height: 47rpx;
      .qty-btn {
        width: 66rpx;
        height: 47rpx;
        line-height: 47rpx;
        text-align: center;
        border: 1rpx solid #a4a4a4;
        font-size: 28rpx;
        color: #a4a4a4;
        &.reduce {
          border-right: 0;
          border-radius: 3rpx 0 0 3rpx;
          &.off {
            border-color: #e3e3e3;
            color: #dedede;
          }
        }
        &.plus {
          border-left: 0;
          border-radius: 0 3rpx 3rpx 0;
          &.off {
            border-color: #e3e3e3;
            color: #dedede;
          }
        }
      }
      .qty-num {
        width: 66rpx;
        height: 47rpx;
        line-height: 47rpx;
        text-align: center;
        border: 1rpx solid #a4a4a4;
        color: #282828;
        font-size: 28rpx;
        input {
          width: 100%;
          height: 100%;
          text-align: center;
        }
      }
    }
  }
}
.invalid-section {
  background: #fff;
  margin-top: 15rpx;
  .invalid-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 66rpx;
    padding: 0 30rpx;
    font-size: 28rpx;
    color: #282828;
    .header-left .iconfont {
      margin-right: 17rpx;
      font-size: 28rpx;
    }
    .header-right {
      font-size: 26rpx;
      color: #999;
      .iconfont {
        font-size: 33rpx;
        margin-right: 8rpx;
        vertical-align: -2rpx;
      }
    }
  }
  .invalid-list {
    &.hide {
      display: none;
    }
    .invalid-item {
      display: flex;
      align-items: center;
      padding: 20rpx 30rpx;
      border-top: 1rpx solid #f5f5f5;
      .invalid-tag {
        width: 70rpx;
        height: 36rpx;
        line-height: 36rpx;
        text-align: center;
        background: #aaa;
        color: #fff;
        font-size: 22rpx;
        border-radius: 3rpx;
        flex-shrink: 0;
      }
      .goods-img {
        width: 140rpx;
        height: 140rpx;
        margin: 0 20rpx;
        border-radius: 6rpx;
        overflow: hidden;
        image {
          width: 100%;
          height: 100%;
        }
      }
      .goods-info {
        flex: 1;
        font-size: 28rpx;
        color: #999;
        height: 140rpx;
        .name {
          width: 100%;
        }
        .sku {
          font-size: 24rpx;
        }
        .tip-text {
          font-size: 26rpx;
          color: #bbb;
        }
      }
    }
  }
}
.load-more {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 80rpx;
  font-size: 24rpx;
  color: #999;
  .loading {
    margin-right: 10rpx;
    animation: rotate 1s linear infinite;
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
.empty-cart {
  margin-top: 171rpx;
  background: #fff;
  .empty-box {
    text-align: center;
    padding: 80rpx 0;
    .tips {
      color: #aaa;
      font-size: 26rpx;
    }
    image {
      width: 414rpx;
      height: 304rpx;
    }
  }
}
.footer-bar {
  z-index: 999;
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 96rpx;
  padding: 0 30rpx;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(10px);
  position: fixed;
  left: 0;
  right: 0;
  border-top: 1rpx solid #eee;
  bottom: 98rpx;
  bottom: calc(98rpx + constant(safe-area-inset-bottom));
  bottom: calc(98rpx + env(safe-area-inset-bottom));
  &.active {
    bottom: 0;
  }
  .bar-left {
    font-size: 28rpx;
    color: #282828;
    text {
      margin-left: 16rpx;
    }
  }
  .bar-right {
    display: flex;
    align-items: center;
    .total {
      font-size: 30rpx;
    }
    .submit-btn {
      color: #fff;
      font-size: 30rpx;
      width: 226rpx;
      height: 70rpx;
      border-radius: 35rpx;
      text-align: center;
      line-height: 70rpx;
      margin-left: 22rpx;
      background: var(--view-theme);
      border: none;
    }
  }
  .bar-actions {
    display: flex;
    align-items: center;
    .action-btn {
      font-size: 28rpx;
      color: #999;
      border: 1rpx solid #999;
      border-radius: 35rpx;
      width: 160rpx;
      height: 60rpx;
      text-align: center;
      line-height: 60rpx;
      background: transparent;
      margin-left: 17rpx;
      &.danger {
        border-color: var(--view-theme);
        color: var(--view-theme);
      }
    }
  }
}
.footer-hold {
  height: 100rpx;
  height: calc(100rpx + constant(safe-area-inset-bottom));
  height: calc(100rpx + env(safe-area-inset-bottom));
}
</style>
