<template>
  <div class="partner-page">
    <div class="container">
      <!-- 标题区域 -->
      <div class="partner-banner">
        <h1 class="banner-title">合作共赢·诚邀优秀原厂/代理商入驻</h1>
        <div class="contact-info">
          <p class="contact-label">我要合作</p>
          <p class="contact-item">联系人：黄先生</p>
          <p class="contact-item">电话：13692180318</p>
          <p class="contact-item">邮箱：harris.huang@oneyac.com</p>
          <p class="contact-item">微信：444849432</p>
        </div>
      </div>

      <!-- 合作申请表单 -->
      <div class="apply-form-card">
        <h2 class="form-title">合作申请</h2>
        <form class="apply-form" @submit.prevent="submitForm">
          <div class="form-row">
            <label class="form-label">* 公司名称：</label>
            <input v-model="form.companyName" type="text" class="form-input" placeholder="请输入公司名称" />
          </div>
          <div class="form-row">
            <label class="form-label">* 商品品牌：</label>
            <input v-model="form.brand" type="text" class="form-input" placeholder="请输入商品品牌" />
          </div>
          <div class="form-row">
            <label class="form-label">* 类别：</label>
            <div class="radio-group">
              <label class="radio-item">
                <input type="radio" v-model="form.category" value="原厂" class="radio-input" />
                <span class="radio-custom"></span>
                <span class="radio-text">原厂</span>
              </label>
              <label class="radio-item">
                <input type="radio" v-model="form.category" value="代理商" class="radio-input" />
                <span class="radio-custom"></span>
                <span class="radio-text">代理商</span>
              </label>
            </div>
          </div>
          <div class="form-row">
            <label class="form-label">* 联系人：</label>
            <input v-model="form.contactName" type="text" class="form-input" placeholder="请输入联系人" />
          </div>
          <div class="form-row">
            <label class="form-label">* 手机号：</label>
            <input v-model="form.phone" type="tel" class="form-input" placeholder="请输入手机号" />
          </div>
          <div class="form-row">
            <label class="form-label">* 邮箱：</label>
            <input v-model="form.email" type="email" class="form-input" placeholder="请输入邮箱" />
          </div>
          <div class="form-row submit-row">
            <button type="submit" class="btn-submit">提交</button>
          </div>
        </form>
      </div>

      <!-- 入驻优势 -->
      <div class="advantages-card">
        <h2 class="advantages-title">入驻优势</h2>
        <div class="advantages-list">
          <div class="advantage-item">✓ 原厂原装产品</div>
          <div class="advantage-item">✓ 一对一专属客服跟单</div>
          <div class="advantage-item">✓ 专业的运营推广团队</div>
          <div class="advantage-item">✓ 高效智能的仓储管理</div>
          <div class="advantage-item">✓ 线上线下全面整合资源</div>
          <div class="advantage-item">✓ 大数据分析及营销服务</div>
        </div>
      </div>

      <!-- 底部平台优势 -->
      <div class="platform-advantages">
        <div class="platform-advantage-item">
          <div class="advantage-char">优</div>
          <div class="advantage-text">原厂授权 正品保障</div>
        </div>
        <div class="platform-advantage-item">
          <div class="advantage-char">快</div>
          <div class="advantage-text">自营现货 极速发货</div>
        </div>
        <div class="platform-advantage-item">
          <div class="advantage-char">全</div>
          <div class="advantage-text">品类齐全 一站采购</div>
        </div>
        <div class="platform-advantage-item">
          <div class="advantage-char">省</div>
          <div class="advantage-text">满299包邮 省心省事</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'partner_detail',
  auth: false,
  data() {
    return {
      form: {
        companyName: '',
        brand: '',
        category: '原厂',
        contactName: '',
        phone: '',
        email: ''
      }
    };
  },
  head() {
    return { title: '供应商合作' };
  },
  methods: {
    async submitForm() {
      // 简单验证
      if (!this.form.companyName || !this.form.brand || !this.form.contactName || !this.form.phone || !this.form.email) {
        this.$message && this.$message.warning('请填写必填项');
        return;
      }
      try {
        const res = await this.$axios.post('/pc/partner_submit', this.form);
        if (res.data.code === 200) {
          this.$message && this.$message.success('提交成功，我们会尽快联系您');
          // 清空表单
          this.form = {
            companyName: '',
            brand: '',
            category: '原厂',
            contactName: '',
            phone: '',
            email: ''
          };
        } else {
          this.$message && this.$message.error(res.data.msg || '提交失败');
        }
      } catch (e) {
        console.error('提交失败', e);
        this.$message && this.$message.error('提交失败，请稍后重试');
      }
    }
  }
};
</script>

<style scoped lang="scss">
.partner-page {
  background-color: #f5f5f5;
  font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
  font-weight: bold;
  min-height: 100vh;
  padding: 20px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

/* 顶部横幅 */
.partner-banner {
  background: linear-gradient(135deg, #e93323 0%, #d42b1c 100%);
  border-radius: 6px;
  padding: 40px;
  text-align: center;
  margin-bottom: 20px;
  color: #fff;

  .banner-title {
    font-size: 28px;
    margin: 0 0 30px 0;
    font-weight: bold;
  }

  .contact-info {
    text-align: left;
    display: inline-block;

    .contact-label {
      font-size: 18px;
      margin: 0 0 12px 0;
      text-decoration: underline;
    }

    .contact-item {
      font-size: 15px;
      margin: 6px 0;
      font-weight: normal;
    }
  }
}

/* 申请表单 */
.apply-form-card {
  background-color: #fff;
  border-radius: 6px;
  padding: 28px;
  margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);

  .form-title {
    font-size: 20px;
    color: #333;
    margin: 0 0 24px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e93323;
  }

  .apply-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .form-row {
    display: flex;
    align-items: center;

    .form-label {
      width: 120px;
      font-size: 15px;
      color: #333;
      text-align: right;
      padding-right: 16px;
      flex-shrink: 0;
    }

    .form-input {
      flex: 1;
      max-width: 400px;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      color: #333;
      font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
      font-weight: bold;

      &:focus {
        outline: none;
        border-color: #e93323;
        box-shadow: 0 0 0 2px rgba(233, 51, 35, 0.1);
      }

      &::placeholder {
        color: #bbb;
        font-weight: normal;
      }
    }

    .radio-group {
      display: flex;
      gap: 30px;
      flex: 1;

      .radio-item {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        font-size: 14px;
        color: #333;
        font-weight: normal;

        .radio-input {
          display: none;
        }

        .radio-custom {
          width: 16px;
          height: 16px;
          border: 1px solid #ccc;
          border-radius: 50%;
          display: inline-block;
          position: relative;
          flex-shrink: 0;
        }

        .radio-input:checked + .radio-custom {
          border-color: #e93323;
          background-color: #e93323;

          &::after {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #fff;
          }
        }
      }
    }

    .submit-row {
      padding-left: 120px;
    }

    .btn-submit {
      padding: 10px 40px;
      background-color: #e93323;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 15px;
      cursor: pointer;
      font-weight: bold;
      font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
      transition: background 0.2s;

      &:hover {
        background-color: #d42b1c;
      }
    }
  }
}

/* 入驻优势 */
.advantages-card {
  background-color: #fff;
  border-radius: 6px;
  padding: 28px;
  margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);

  .advantages-title {
    font-size: 20px;
    color: #333;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e93323;
  }

  .advantages-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;

    .advantage-item {
      font-size: 15px;
      color: #333;
      padding: 8px 0;
    }
  }
}

/* 底部平台优势 */
.platform-advantages {
  display: flex;
  background-color: #fff;
  border-radius: 6px;
  padding: 24px 0;
  margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);

  .platform-advantage-item {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border-right: 1px solid #eee;
    padding: 0 20px;

    &:last-child {
      border-right: none;
    }

    .advantage-char {
      width: 40px;
      height: 40px;
      background-color: #e93323;
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      font-weight: bold;
      flex-shrink: 0;
    }

    .advantage-text {
      font-size: 16px;
      color: #333;
      font-weight: bold;
    }
  }
}
</style>