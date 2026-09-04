<template>
  <div class="bom-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1 class="page-title">粘贴物料清单文本</h1>
        <p class="page-desc">快速导入BOM，智能识别型号和数量</p>
      </div>

      <!-- 示例说明 -->
      <div class="info-box">
        <p><strong>示例</strong> 型号和数量间用空格隔开，多个型号用回车隔开</p>
        <pre class="example">AC0201FR-0710KL 2500
MR04X1000FTL 2000
EEFSX0D471E4 3000</pre>
        <p class="note">若需要导入Excel格式的BOM单请到唯样商城PC端操作。</p>
      </div>

      <!-- 输入区域 -->
      <div class="input-section">
        <label class="input-label">粘贴你的BOM清单：</label>
        <textarea
          v-model="bomText"
          placeholder="在这里粘贴你的物料清单，格式：型号 数量&#10;例如：&#10;AC0201FR-0710KL 2500&#10;MR04X1000FTL 2000"
          class="bom-textarea"
          @input="parseBom"
        ></textarea>
        <div class="input-footer">
          <span class="line-count">已识别 {{ bomList.length }} 行</span>
          <button class="btn-clear" @click="clearAll">清空</button>
        </div>
      </div>

      <!-- 解析结果表格 -->
      <div class="result-section" v-if="bomList.length > 0">
        <div class="result-header">
          <h2>识别结果</h2>
          <span class="result-count">共 {{ totalItems }} 个物料</span>
        </div>
        <div class="table-container">
          <table class="bom-table">
            <thead>
              <tr>
                <th width="50">#</th>
                <th>型号</th>
                <th width="120">数量</th>
                <th width="60">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in bomList" :key="index" :class="{ error: !item.quantity }">
                <td>{{ index + 1 }}</td>
                <td>
                  <input
                    v-model="item.model"
                    type="text"
                    placeholder="型号"
                    @blur="mergeSimilar"
                  />
                </td>
                <td>
                  <input
                    v-model="item.quantity"
                    type="number"
                    min="1"
                    placeholder="数量"
                  />
                </td>
                <td>
                  <button class="btn-delete" @click="deleteItem(index)">删除</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 底部操作按钮 -->
      <div class="action-section" v-if="bomList.length > 0">
        <button class="btn-primary" @click="submitBom" :disabled="!hasValidItems">
          搜索物料
        </button>
        <button class="btn-secondary" @click="clearAll">重新输入</button>
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
      bomList: []
    };
  },
  head() {
    return { title: 'BOM粘贴 - 物料清单导入' };
  },
  computed: {
    totalItems() {
      return this.bomList.filter(item => item.model && item.quantity).length;
    },
    hasValidItems() {
      return this.totalItems > 0;
    }
  },
  methods: {
    parseBom() {
      if (!this.bomText.trim()) {
        this.bomList = [];
        return;
      }

      const lines = this.bomText.trim().split('\n');
      this.bomList = lines.map(line => {
        const trimmed = line.trim();
        if (!trimmed) return { model: '', quantity: '' };

        // 匹配：型号 数量 的格式
        const parts = trimmed.split(/\s+/);
        if (parts.length >= 2) {
          // 最后一个部分尝试转为数字（数量）
          const quantity = parseInt(parts.pop());
          const model = parts.join(' ');
          return {
            model: model.trim(),
            quantity: isNaN(quantity) ? '' : quantity
          };
        } else {
          // 只有型号，数量留空
          return {
            model: trimmed,
            quantity: ''
          };
        }
      }).filter(item => item.model.trim()); // 过滤空行

      this.mergeSimilar();
    },
    mergeSimilar() {
      // 合并相同型号，数量相加
      const map = new Map();
      this.bomList.forEach(item => {
        if (!item.model) return;
        const key = item.model.trim();
        if (!key) return;
        if (map.has(key)) {
          const exist = map.get(key);
          if (item.quantity) {
            exist.quantity = (parseInt(exist.quantity) || 0) + (parseInt(item.quantity) || 0);
          }
        } else {
          map.set(key, { ...item });
        }
      });
      if (map.size !== this.bomList.length) {
        this.bomList = Array.from(map.values());
      }
    },
    deleteItem(index) {
      this.bomList.splice(index, 1);
    },
    clearAll() {
      this.bomText = '';
      this.bomList = [];
    },
    submitBom() {
      // 过滤有效项，跳转到搜索结果页
      const validItems = this.bomList
        .filter(item => item.model && item.quantity)
        .map(item => ({
          model: item.model.trim(),
          quantity: parseInt(item.quantity)
        }));

      if (validItems.length === 0) {
        this.$message.warning('请至少填写一个有效的物料');
        return;
      }

      // 这里可以跳转到搜索结果页面，或者保存到状态后跳转
      // 示例：跳转到商品列表页并携带BOM信息
      this.$router.push({
        path: '/goods_list',
        query: {
          bom: encodeURIComponent(JSON.stringify(validItems))
        }
      });
    }
  }
};
</script>

<style scoped lang="scss">
.bom-page {
  background-color: #f5f6f8;
  min-height: 100vh;
  padding: 30px 0;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 20px;
}

/* 页面标题 */
.page-header {
  text-align: center;
  margin-bottom: 24px;

  .page-title {
    font-size: 28px;
    color: #333;
    margin: 0 0 8px 0;
    font-weight: 600;
  }

  .page-desc {
    font-size: 14px;
    color: #999;
    margin: 0;
    font-weight: normal;
  }
}

/* 说明框 */
.info-box {
  background: #fff;
  border-radius: 6px;
  padding: 20px 24px;
  margin-bottom: 24px;
  border-left: 4px solid #1890ff;

  p {
    margin: 0 0 12px 0;
    color: #666;
    font-size: 14px;

    strong {
      color: #333;
    }
  }

  .example {
    background: #f6f8fa;
    padding: 12px 16px;
    border-radius: 4px;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 13px;
    color: #333;
    white-space: pre;
    margin: 12px 0;
    overflow-x: auto;
  }

  .note {
    color: #999;
    font-size: 13px;
    margin-bottom: 0;
  }
}

/* 输入区域 */
.input-section {
  background: #fff;
  border-radius: 6px;
  padding: 24px;
  margin-bottom: 24px;

  .input-label {
    display: block;
    font-size: 15px;
    color: #333;
    margin-bottom: 12px;
    font-weight: 500;
  }

  .bom-textarea {
    width: 100%;
    min-height: 260px;
    padding: 16px;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    font-size: 14px;
    font-family: 'Consolas', 'Monaco', monospace;
    line-height: 1.6;
    resize: vertical;
    outline: none;
    transition: border-color 0.2s;

    &:focus {
      border-color: #1890ff;
      box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
    }
  }

  .input-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;

    .line-count {
      font-size: 13px;
      color: #999;
    }

    .btn-clear {
      background: none;
      border: none;
      color: #1890ff;
      font-size: 13px;
      cursor: pointer;
      padding: 4px 12px;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

/* 结果表格 */
.result-section {
  background: #fff;
  border-radius: 6px;
  padding: 24px;
  margin-bottom: 24px;

  .result-header {
    display: flex;
    align-items: center;
    margin-bottom: 16px;

    h2 {
      font-size: 16px;
      color: #333;
      margin: 0;
      font-weight: 600;
    }

    .result-count {
      margin-left: 12px;
      font-size: 13px;
      color: #999;
      background: #f0f0f0;
      padding: 2px 8px;
      border-radius: 10px;
    }
  }

  .table-container {
    overflow-x: auto;
  }

  .bom-table {
    width: 100%;
    border-collapse: collapse;

    th {
      background: #fafafa;
      text-align: left;
      padding: 12px 10px;
      font-size: 13px;
      font-weight: 600;
      color: #666;
      border: 1px solid #e8e8e8;
    }

    td {
      padding: 10px;
      border: 1px solid #e8e8e8;

      input {
        width: 100%;
        border: 1px solid #d9d9d9;
        border-radius: 4px;
        padding: 6px 8px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;

        &:focus {
          border-color: #1890ff;
        }
      }
    }

    tr.error {
      background-color: #fff2f0;

      td {
        border-color: #ffccc7;
      }
    }

    .btn-delete {
      background: #fff1f0;
      color: #ff4d4f;
      border: 1px solid #ffccc7;
      border-radius: 4px;
      padding: 4px 8px;
      font-size: 12px;
      cursor: pointer;

      &:hover {
        background: #ff4d4f;
        color: #fff;
        border-color: #ff4d4f;
      }
    }
  }
}

/* 操作按钮 */
.action-section {
  display: flex;
  gap: 16px;
  justify-content: center;

  .btn-primary {
    min-width: 160px;
    background: #1890ff;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 12px 24px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
      background: #40a9ff;
    }

    &:disabled {
      background: #bfbfbf;
      cursor: not-allowed;
    }
  }

  .btn-secondary {
    min-width: 120px;
    background: #f5f5f5;
    color: #666;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    padding: 12px 24px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      background: #eaeaea;
      color: #333;
    }
  }
}

@media (max-width: 768px) {
  .bom-page {
    padding: 16px 0;
  }

  .page-header .page-title {
    font-size: 22px;
  }

  .input-section,
  .result-section {
    padding: 16px;
  }

  .action-section {
    flex-direction: column;

    .btn-primary,
    .btn-secondary {
      width: 100%;
    }
  }
}
</style>
