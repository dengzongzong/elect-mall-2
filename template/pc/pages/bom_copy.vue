<template>
  <div class="bom-page">
    <div class="container">
      <!-- Hero 区域 -->
      <div class="bom-hero">
        <h1 class="bom-title">智能BOM 您的一站式配单平台</h1>
        <div class="bom-features">
          <div class="feature-item">
            <span class="feature-icon">&#x1f4a1;</span>
            <span class="feature-text">智能物料推荐</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">&#x1f4e6;</span>
            <span class="feature-text">海量现货库存</span>
          </div>
          <div class="feature-item">
            <span class="feature-icon">&#x1f4ca;</span>
            <span class="feature-text">快速统计成本</span>
          </div>
        </div>
      </div>

      <div class="bom-methods">
        <!-- 方法一：粘贴文本 -->
        <div class="bom-method-card">
          <h2 class="method-title">粘贴物料清单文本</h2>
          <div class="method-body">
            <textarea
              v-model="bomText"
              class="bom-textarea"
              placeholder="可手动输入或Excel复制粘贴,示例：AC0201FR-0710KL 250010uF ±10% 16V 30000603 ±20% 10V 1800"
              rows="6"
            ></textarea>
            <div class="method-actions">
              <label class="checkbox-label">
                <input type="checkbox" v-model="onlySelf" class="checkbox-input" />
                <span class="checkbox-custom"></span>
                <span class="checkbox-text">只匹配自营商品</span>
              </label>
              <button class="btn-process" @click="handleBomText">开始处理</button>
            </div>
          </div>
        </div>

        <!-- 方法二：上传文件 -->
        <div class="bom-method-card">
          <h2 class="method-title">上传一个BOM</h2>
          <div class="method-body">
            <div class="upload-area" @click="triggerUpload" @dragover.prevent @drop.prevent="handleDrop">
              <div class="upload-icon">&#x1f4c4;</div>
              <p class="upload-text">将文件拖拽到此框 或 点击此框上传BOM文件</p>
              <p class="upload-hint">支持csv/xls/xlsx格式文件, <a :href="bomTemplateUrl" class="download-template">下载BOM模板</a></p>
              <input
                ref="fileInput"
                type="file"
                accept=".csv,.xls,.xlsx"
                @change="handleFileUpload"
                style="display: none"
              />
            </div>
            <div class="method-actions">
              <label class="checkbox-label">
                <input type="checkbox" v-model="onlySelfUpload" class="checkbox-input" />
                <span class="checkbox-custom"></span>
                <span class="checkbox-text">只匹配自营商品</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- 管理BOM列表 -->
      <div class="bom-manage">
        <nuxt-link to="/user/bom_list" class="manage-link">管理BOM列表</nuxt-link>
      </div>

      <!-- 底部优势 -->
      <div class="bom-advantages">
        <div class="advantage-item">
          <div class="advantage-icon">优</div>
          <div class="advantage-text">
            <span class="advantage-title">原厂授权 正品保障</span>
          </div>
        </div>
        <div class="advantage-item">
          <div class="advantage-icon">快</div>
          <div class="advantage-text">
            <span class="advantage-title">自营现货 极速发货</span>
          </div>
        </div>
        <div class="advantage-item">
          <div class="advantage-icon">全</div>
          <div class="advantage-text">
            <span class="advantage-title">品类齐全 一站采购</span>
          </div>
        </div>
        <div class="advantage-item">
          <div class="advantage-icon">省</div>
          <div class="advantage-text">
            <span class="advantage-title">满299包邮 省心省事</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'bom_copy',
  auth: false,
  data() {
    return {
      bomText: '',
      onlySelf: true,
      onlySelfUpload: true,
      bomTemplateUrl: '/bom_template.xlsx',
      uploadedFile: null
    };
  },
  head() {
    return { title: '智能BOM配单' };
  },
  methods: {
    async handleBomText() {
      if (!this.bomText.trim()) {
        this.$message && this.$message.warning('请输入物料清单');
        return;
      }
      try {
        const res = await this.$axios.post('/pc/bom_process_text', {
          text: this.bomText,
          only_self: this.onlySelf ? 1 : 0
        });
        if (res.data && res.data.list) {
          this.$router.push({
            path: '/bom_result',
            query: { data: JSON.stringify(res.data.list) }
          });
        }
      } catch (e) {
        console.error('BOM处理失败', e);
      }
    },
    triggerUpload() {
      this.$refs.fileInput.click();
    },
    handleDrop(e) {
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        this.uploadedFile = files[0];
        this.submitFile(files[0]);
      }
    },
    handleFileUpload(e) {
      const files = e.target.files;
      if (files.length > 0) {
        this.uploadedFile = files[0];
        this.submitFile(files[0]);
      }
    },
    async submitFile(file) {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('only_self', this.onlySelfUpload ? 1 : 0);
      try {
        const res = await this.$axios.post('/pc/bom_upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        if (res.data && res.data.list) {
          this.$router.push({
            path: '/bom_result',
            query: { data: JSON.stringify(res.data.list) }
          });
        }
      } catch (e) {
        console.error('BOM上传失败', e);
      }
    }
  }
};
</script>

<style scoped lang="scss">
.bom-page {
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

/* Hero 区域 */
.bom-hero {
  background: linear-gradient(135deg, #e93323 0%, #d42b1c 100%);
  border-radius: 6px;
  padding: 40px;
  text-align: center;
  margin-bottom: 20px;
  color: #fff;

  .bom-title {
    font-size: 32px;
    margin: 0 0 24px 0;
    font-weight: bold;
  }

  .bom-features {
    display: flex;
    justify-content: center;
    gap: 60px;
  }

  .feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;

    .feature-icon {
      font-size: 24px;
    }

    .feature-text {
      font-weight: bold;
    }
  }
}

/* 方法卡片 */
.bom-methods {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.bom-method-card {
  flex: 1;
  background-color: #fff;
  border-radius: 6px;
  padding: 28px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);

  .method-title {
    font-size: 20px;
    color: #333;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e93323;
  }

  .method-body {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
}

.bom-textarea {
  width: 100%;
  min-height: 120px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 13px;
  color: #333;
  resize: vertical;
  font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
  font-weight: bold;
  line-height: 1.6;

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

.method-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  font-size: 14px;
  color: #666;
  font-weight: normal;

  .checkbox-input {
    display: none;
  }

  .checkbox-custom {
    width: 16px;
    height: 16px;
    border: 1px solid #ccc;
    border-radius: 2px;
    display: inline-block;
    position: relative;
    flex-shrink: 0;
  }

  .checkbox-input:checked + .checkbox-custom {
    background-color: #e93323;
    border-color: #e93323;

    &::after {
      content: '';
      position: absolute;
      left: 5px;
      top: 2px;
      width: 5px;
      height: 9px;
      border: solid #fff;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
  }

  .checkbox-text {
    font-weight: normal;
  }
}

.btn-process {
  padding: 10px 32px;
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

/* 上传区域 */
.upload-area {
  border: 2px dashed #ddd;
  border-radius: 6px;
  padding: 40px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
  background-color: #fafafa;

  &:hover {
    border-color: #e93323;
    background-color: #fff8f7;
  }

  .upload-icon {
    font-size: 40px;
    margin-bottom: 12px;
  }

  .upload-text {
    font-size: 15px;
    color: #333;
    margin: 0 0 8px 0;
  }

  .upload-hint {
    font-size: 13px;
    color: #999;
    margin: 0;
    font-weight: normal;

    .download-template {
      color: #e93323;
      text-decoration: none;
      font-weight: bold;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

/* 管理BOM列表 */
.bom-manage {
  text-align: right;
  margin-bottom: 20px;

  .manage-link {
    color: #e93323;
    font-size: 14px;
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }
}

/* 底部优势 */
.bom-advantages {
  display: flex;
  background-color: #fff;
  border-radius: 6px;
  padding: 24px 0;
  margin-bottom: 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);

  .advantage-item {
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
  }

  .advantage-icon {
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

  .advantage-title {
    font-size: 16px;
    color: #333;
    font-weight: bold;
  }
}
</style>