<template>
  <div class="login-page">
    <!-- 顶部导航 -->
    <div class="login-header">
      <div class="header-inner">
        <nuxt-link to="/" class="logo-link">
          <img :src="info.logoUrl" class="logo-img" />
        </nuxt-link>
      </div>
    </div>

    <!-- 登录主体 -->
    <div class="login-body">
      <div class="login-card">
        <!-- 标签切换 -->
        <div class="tab-bar">
          <span class="tab-item" :class="{ active: current === 1 }" @click="current = 1">密码登录</span>
          <span class="tab-item" :class="{ active: current === 2 }" @click="current = 2">短信登录/注册</span>
          <span class="tab-item wx-tab" v-if="appidNum" @click="ewmLogin">
            <span class="iconfont icon-weixindenglu1"></span> 微信登录
          </span>
        </div>

        <!-- 密码登录 -->
        <div class="form-wrapper" v-show="current === 1">
          <div class="form-group">
            <label class="form-label">账号</label>
            <div class="input-wrap">
              <input type="text" placeholder="用户名/手机号码" v-model="account" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">密码</label>
            <div class="input-wrap">
              <input type="password" placeholder="密码" v-model="password" class="form-input" />
            </div>
          </div>
          <div class="options-row">
            <label class="auto-login">
              <input type="checkbox" v-model="autoLogin" class="custom-checkbox" />
              <span class="checkbox-text">下次自动登录</span>
            </label>
            <nuxt-link to="/forgot_password" class="forgot-link">忘记密码</nuxt-link>
          </div>
          <div class="agree-row">
            <el-checkbox v-model="agreement"></el-checkbox>
            <span class="agree-text">我已阅读并同意<span class="agree-link" @click="agreementTap(4)">《用户协议》</span>和<span class="agree-link" @click="agreementTap(3)">《隐私协议》</span></span>
          </div>
          <button class="login-btn" @click="loginH5">立即登录</button>
          <div class="register-row">
            <span class="no-account">还没有账号？</span>
            <span class="register-link" @click="current = 2">短信快速注册</span>
          </div>
        </div>

        <!-- 短信登录/注册 -->
        <div class="form-wrapper" v-show="current === 2">
          <div class="form-group">
            <label class="form-label">手机号</label>
            <div class="input-wrap phone-input">
              <span class="phone-prefix">+86</span>
              <input type="text" placeholder="请输入手机号码" v-model="account" maxlength="11" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">验证码</label>
            <div class="input-wrap code-input">
              <input type="text" placeholder="请输入验证码" v-model="captcha" class="form-input" />
              <button class="code-btn" :disabled="disabled" :class="{ on: disabled }" @click="getVerify">
                {{ text }}
              </button>
            </div>
          </div>
          <div class="agree-row">
            <el-checkbox v-model="agreement"></el-checkbox>
            <span class="agree-text">我已阅读并同意<span class="agree-link" @click="agreementTap(4)">《用户协议》</span>和<span class="agree-link" @click="agreementTap(3)">《隐私协议》</span></span>
          </div>
          <button class="login-btn" @click="loginMobile">登录/注册</button>
        </div>
      </div>
    </div>

    <!-- 底部信息 -->
    <div class="login-footer">
      <div class="footer-inner">
        <span>联系电话：{{ info.contact_number }}</span>
        <span class="footer-divider">|</span>
        <span>地址：{{ info.company_address }}</span>
      </div>
      <div class="footer-record">
        {{ info.copyright }}<a href="https://beian.miit.gov.cn/" target="_blank" class="beian">{{ info.record_No }}</a>
      </div>
    </div>

    <!-- 协议弹窗 -->
    <el-dialog class="detail-bd" :title="agreementTitle" :visible.sync="userAgreement" :show-close="false" width="900px" center>
      <div class="userAgree" v-html="agreementCon"></div>
      <span slot="footer" class="dialog-footer">
        <el-button type="primary" @click="agreementClose">确 定</el-button>
      </span>
    </el-dialog>

    <!-- 验证码滑块 -->
    <Verify v-if="verifyModal" @success="success" captchaType="blockPuzzle" :imgSize="{ width: '330px', height: '155px' }" ref="verify"></Verify>
  </div>
</template>

<script>
import sendVerifyCode from "@/mixins/SendVerifyCode";
import Verify from "@/components/verifition/Verify";

export default {
  name: "login",
  auth: false,
  mixins: [sendVerifyCode],
  components: { Verify },
  data() {
    return {
      verifyModal: false,
      current: 1, // 1=密码登录, 2=短信登录/注册
      account: "",
      password: "",
      captcha: "",
      keyCode: "",
      info: "",
      appidNum: "",
      hosts: "",
      codes: "",
      fromPath: "",
      agreement: false,
      autoLogin: true,
      userAgreement: false,
      agreementCon1: "",
      agreementCon2: "",
      agreementCon: "",
      agreementTitle: ""
    };
  },
  async asyncData({ $axios, query }) {
    const keyCode = await $axios.$get("/verify_code");
    const companyInfo = await $axios.$get("/pc/get_company_info");
    const appidNum = await $axios.$get("/pc/get_appid");
    const agreement1 = await $axios.$get("/get_agreement/4");
    const agreement2 = await $axios.$get("/get_agreement/3");
    return {
      keyCode: keyCode.key,
      info: companyInfo,
      appidNum: appidNum.appid,
      codes: query.code || "",
      agreementCon1: agreement1.content,
      agreementCon2: agreement2.content
    };
  },
  fetch({ store }) {
    store.commit("isHeader", false);
    store.commit("isFooter", false);
  },
  head() {
    return { title: this.$store.state.titleCon };
  },
  created() {
    if (this.$auth.loggedIn) {
      this.$router.push("/");
    }
  },
  mounted() {
    window.addEventListener("keydown", this.keyDown);
    this.hosts = location.origin + location.pathname;
    this.fromPath = this.$cookies.get("fromPath");
    if (this.codes) {
      this.loginCode();
    }
  },
  destroyed() {
    window.removeEventListener("keydown", this.keyDown, false);
  },
  methods: {
    keyDown(e) {
      if (e.keyCode === 13) {
        if (this.current === 1) {
          this.loginH5();
        } else if (this.current === 2) {
          this.loginMobile();
        }
      }
    },
    ewmLogin() {
      if (!this.agreement) return this.$message.error("请确认阅读用户协议");
      let hosts = encodeURIComponent(this.hosts);
      window.location.href = `https://open.weixin.qq.com/connect/qrconnect?appid=${this.appidNum}&redirect_uri=${hosts}&response_type=code&scope=snsapi_login&state=EqMkUDWh8F3euWlt23jHJ8ZJuaTAVPZyiKEoq5U0`;
    },
    agreementTap(type) {
      if (type == 4) {
        this.agreementTitle = "用户协议";
        this.agreementCon = this.agreementCon1;
      } else {
        this.agreementTitle = "隐私协议";
        this.agreementCon = this.agreementCon2;
      }
      this.userAgreement = true;
    },
    agreementClose() {
      this.userAgreement = false;
      this.agreement = true;
    },
    async loginCode() {
      let that = this;
      await that.$auth
        .loginWith("local3", { params: { code: this.codes } })
        .then(() => {
          that.isShow = false;
          if (this.fromPath) {
            let path = this.fromPath.split(that.$router.history.base);
            let fromPath = path.join("");
            that.$router.push(fromPath);
          } else {
            that.$router.push("/");
          }
          that.$cookies.remove("fromPath");
        })
        .catch(err => {});
    },
    async loginH5() {
      let that = this;
      if (!that.agreement) return that.$message.error("请确认阅读用户协议");
      if (!that.account) return that.$message.error("请填写手机号码");
      if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(that.account))
        return that.$message.error("请输入正确的手机号码");
      if (!that.password) return that.$message.error("请填写密码");
      let userInfo = {
        account: that.account,
        password: that.password
      };
      await that.$auth
        .loginWith("local1", { data: userInfo })
        .then(() => {
          that.isShow = false;
          if (this.fromPath) {
            let path = this.fromPath.split(that.$router.history.base);
            let fromPath = path.join("");
            that.$router.push(fromPath);
          } else {
            that.$router.push("/");
          }
          that.$cookies.remove("fromPath");
        })
        .catch(err => {
          that.$message.error(err);
        });
    },
    getVerify() {
      if (!this.account) return this.$message.error("请填写手机号码");
      if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(this.account))
        return this.$message.error("请输入正确的手机号码");
      if (!this.agreement) return this.$message.error("请确认阅读用户协议");
      this.verifyModal = true;
      this.$nextTick(e => {
        this.$refs.verify.show();
      });
    },
    success(params) {
      this.closeModel(params);
    },
    closeModel(params) {
      this.code(params);
    },
    async loginMobile() {
      let that = this;
      if (!that.agreement) return that.$message.error("请确认阅读用户协议");
      if (!that.account) return that.$message.error("请填写手机号码");
      if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(that.account))
        return that.$message.error("请输入正确的手机号码");
      if (!that.captcha) return that.$message.error("请填写验证码");
      if (!/^[\w\d]+$/i.test(that.captcha))
        return that.$message.error("请输入正确的验证码");
      let userInfo = {
        phone: that.account,
        captcha: that.captcha
      };
      await that.$auth
        .loginWith("local2", { data: userInfo })
        .then(() => {
          that.isShow = false;
          if (this.fromPath) {
            let path = this.fromPath.split(that.$router.history.base);
            let fromPath = path.join("");
            that.$router.push(fromPath);
          } else {
            that.$router.push("/");
          }
          that.$cookies.remove("fromPath");
        })
        .catch(err => {
          that.$message.error("验证码错误");
        });
    },
    async code(data) {
      let that = this;
      if (!that.agreement) return that.$message.error("请确认阅读用户协议");
      if (!that.account) return that.$message.error("请填写手机号码");
      if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(that.account))
        return that.$message.error("请输入正确的手机号码");
      await this.$axios
        .post("/register/verify", {
          phone: that.account,
          type: "mobile",
          key: that.keyCode,
          captchaType: "blockPuzzle",
          captchaVerification: data.captchaVerification
        })
        .then(res => {
          that.$message.success(res.msg);
          that.sendCode();
        })
        .catch(err => {
          that.$message.error(err);
        });
    }
  }
};
</script>

<style scoped lang="scss">
.login-page {
  background-color: #f5f5f5;
  min-height: 100vh;
  font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
  font-weight: bold;
  display: flex;
  flex-direction: column;
}

/* 顶部导航 */
.login-header {
  background-color: #fff;
  border-bottom: 1px solid #e8e8e8;
  height: 80px;
  display: flex;
  align-items: center;

  .header-inner {
    max-width: 1200px;
    width: 100%;
    margin: 0 auto;
    padding: 0 15px;
  }

  .logo-img {
    height: 40px;
    cursor: pointer;
  }
}

/* 登录主体 */
.login-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 15px;
}

.login-card {
  width: 440px;
  background-color: #fff;
  border-radius: 6px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  padding: 36px 40px 40px;
}

/* Tab 切换 */
.tab-bar {
  display: flex;
  gap: 0;
  margin-bottom: 30px;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 0;

  .tab-item {
    font-size: 16px;
    color: #666;
    padding: 0 0 12px 0;
    margin-right: 30px;
    cursor: pointer;
    position: relative;
    font-weight: bold;
    transition: color 0.2s;

    &:hover {
      color: #e93323;
    }

    &.active {
      color: #e93323;

      &::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #e93323;
      }
    }

    &.wx-tab {
      float: right;
      margin-right: 0;
      color: #07c160;
      font-size: 15px;

      .iconfont {
        font-size: 18px;
      }

      &.active::after {
        background-color: #07c160;
      }
    }
  }
}

/* 表单 */
.form-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.form-group {
  margin-bottom: 20px;

  .form-label {
    display: block;
    font-size: 14px;
    color: #333;
    margin-bottom: 8px;
    font-weight: bold;
  }

  .input-wrap {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    height: 44px;
    background-color: #fff;
    transition: border-color 0.2s;

    &:focus-within {
      border-color: #e93323;
      box-shadow: 0 0 0 2px rgba(233, 51, 35, 0.08);
    }

    &.phone-input {
      .phone-prefix {
        width: 50px;
        text-align: center;
        color: #666;
        font-size: 14px;
        border-right: 1px solid #ddd;
        line-height: 44px;
        font-weight: normal;
      }
    }

    &.code-input {
      .code-btn {
        width: 110px;
        height: 100%;
        border: 0;
        background: #fff;
        color: #e93323;
        font-size: 14px;
        border-left: 1px solid #ddd;
        cursor: pointer;
        font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
        font-weight: bold;
        white-space: nowrap;
        transition: color 0.2s;

        &:hover:not(.on) {
          color: #d42b1c;
        }

        &.on {
          color: #bbb !important;
          cursor: not-allowed;
        }
      }
    }
  }

  .form-input {
    flex: 1;
    height: 100%;
    padding: 0 14px;
    border: 0;
    outline: none;
    font-size: 14px;
    color: #333;
    background: transparent;
    font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
    font-weight: bold;

    &::placeholder {
      color: #bbb;
      font-weight: normal;
    }
  }
}

/* 选项行 */
.options-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;

  .auto-login {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    font-weight: normal;

    .custom-checkbox {
      width: 16px;
      height: 16px;
      accent-color: #e93323;
      cursor: pointer;
    }

    .checkbox-text {
      font-weight: normal;
    }
  }

  .forgot-link {
    font-size: 14px;
    color: #e93323;
    text-decoration: none;
    font-weight: normal;

    &:hover {
      text-decoration: underline;
    }
  }
}

/* 同意协议 */
.agree-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 20px;

  .agree-text {
    font-size: 12px;
    color: #999;
    font-weight: normal;

    .agree-link {
      color: #e93323;
      cursor: pointer;
      text-decoration: none;
      font-weight: bold;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

/* 登录按钮 */
.login-btn {
  width: 100%;
  height: 46px;
  background-color: #e93323;
  color: #fff;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
  font-weight: bold;
  font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
  transition: background 0.2s;
  margin-bottom: 16px;

  &:hover {
    background-color: #d42b1c;
  }

  &:active {
    background-color: #c0261a;
  }
}

/* 注册行 */
.register-row {
  text-align: center;
  font-size: 14px;

  .no-account {
    color: #666;
    font-weight: normal;
  }

  .register-link {
    color: #e93323;
    cursor: pointer;
    font-weight: bold;

    &:hover {
      text-decoration: underline;
    }
  }
}

/* 底部信息 */
.login-footer {
  background-color: #fff;
  border-top: 1px solid #e8e8e8;
  padding: 20px 0;
  text-align: center;
  font-size: 12px;
  color: #888;

  .footer-inner {
    margin-bottom: 6px;

    .footer-divider {
      margin: 0 16px;
      color: #ddd;
    }
  }

  .footer-record {
    .beian {
      color: #888;
      text-decoration: none;
      margin-left: 8px;

      &:hover {
        color: #e93323;
      }
    }
  }
}
</style>