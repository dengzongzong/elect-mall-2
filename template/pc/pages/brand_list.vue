<template>
  <div class="brand-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1 class="page-title">品牌专区</h1>
        <p class="page-desc">原厂授权代理，正品保障，品质信赖</p>
      </div>

      <!-- 字母索引 -->
      <div class="letter-index">
        <div class="index-bar">
          <span class="index-label">按字母索引：</span>
          <span
            v-for="letter in letters"
            :key="letter"
            :class="['index-letter', { active: currentLetter === letter }]"
            @click="scrollToLetter(letter)"
          >{{ letter }}</span>
        </div>
      </div>

      <!-- 品牌列表 -->
      <div class="brand-list" v-if="brandList.length">
        <div
          v-for="letter in letters"
          :key="letter"
          :ref="'letter-' + letter"
          class="brand-letter-group"
          v-if="getBrandsByLetter(letter).length"
        >
          <h2 class="letter-title">{{ letter }}</h2>
          <div class="brand-grid">
            <div
              class="brand-card"
              v-for="brand in getBrandsByLetter(letter)"
              :key="brand.id"
              @click="goBrand(brand)"
            >
              <div class="brand-logo">
                <span class="brand-placeholder">{{ brand.name_en.substring(0, 2) }}</span>
              </div>
              <div class="brand-info">
                <h3 class="brand-name-en">{{ brand.name_en }}</h3>
                <p class="brand-name-cn">{{ brand.name_cn }}</p>
                <p class="brand-product-count">
                  <span class="count-num">{{ brand.product_count }}</span> 种产品
                </p>
                <span v-if="brand.is_authorized" class="brand-auth-tag">授权代理</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="empty-state" v-else>
        <p>暂无品牌数据</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "brand_list",
  auth: false,
  data() {
    return {
      brandList: [],
      currentLetter: '',
      letters: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '#']
    };
  },
  async asyncData({ app }) {
    const res = await app.$axios.get('/pc/get_brand_list');
    return { brandList: res.data.list || [] };
  },
  head() {
    return { title: '品牌专区' };
  },
  mounted() {
    this.setupScrollSpy();
  },
  methods: {
    getBrandsByLetter(letter) {
      return this.brandList.filter(b => b.initial === letter);
    },
    scrollToLetter(letter) {
      this.currentLetter = letter;
      const el = this.$refs['letter-' + letter];
      if (el && el[0]) {
        el[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },
    setupScrollSpy() {
      window.addEventListener('scroll', () => {
        for (const letter of this.letters) {
          const el = this.$refs['letter-' + letter];
          if (el && el[0]) {
            const rect = el[0].getBoundingClientRect();
            if (rect.top <= 200) {
              this.currentLetter = letter;
            }
          }
        }
      });
    },
    goBrand(brand) {
      this.$router.push({
        path: '/goods_list',
        query: { brand: brand.name_en }
      });
    }
  }
};
</script>

<style scoped lang="scss">
.brand-page {
  background-color: #f5f5f5;
  font-family: "Microsoft YaHei", "微软雅黑", sans-serif;
  font-weight: bold;
  min-height: 100vh;
  padding: 20px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0;
}

/* 页面标题 */
.page-header {
  background-color: #fff;
  border-radius: 4px;
  padding: 32px 40px;
  margin-bottom: 20px;
  text-align: center;

  .page-title {
    font-size: 28px;
    color: #333;
    margin: 0 0 8px 0;
  }

  .page-desc {
    font-size: 14px;
    color: #999;
    margin: 0;
    font-weight: normal;
  }
}

/* 字母索引条 */
.letter-index {
  background-color: #fff;
  border-radius: 4px;
  padding: 12px 24px;
  margin-bottom: 20px;
  position: sticky;
  top: 0;
  z-index: 10;

  .index-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
  }

  .index-label {
    font-size: 13px;
    color: #666;
    margin-right: 8px;
    white-space: nowrap;
    font-weight: normal;
  }

  .index-letter {
    display: inline-block;
    padding: 4px 8px;
    margin: 2px;
    font-size: 13px;
    color: #333;
    cursor: pointer;
    border-radius: 2px;
    transition: all 0.2s;
    font-weight: bold;

    &:hover {
      color: #e93323;
      background-color: #fff0ee;
    }

    &.active {
      color: #fff;
      background-color: #e93323;
    }
  }
}

/* 品牌列表 */
.brand-letter-group {
  margin-bottom: 20px;

  .letter-title {
    font-size: 20px;
    color: #333;
    padding: 12px 0;
    margin: 0 0 12px 0;
    border-bottom: 2px solid #e93323;
  }
}

.brand-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.brand-card {
  width: calc(25% - 9px);
  background-color: #fff;
  border-radius: 4px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  border: 1px solid #eaeaea;

  &:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    border-color: #e93323;
    transform: translateY(-2px);
  }

  .brand-logo {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    flex-shrink: 0;

    .brand-placeholder {
      font-size: 18px;
      color: #999;
      font-weight: bold;
    }
  }

  .brand-info {
    flex: 1;
    min-width: 0;

    .brand-name-en {
      font-size: 15px;
      color: #333;
      margin: 0 0 4px 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .brand-name-cn {
      font-size: 13px;
      color: #666;
      margin: 0 0 6px 0;
      font-weight: normal;
    }

    .brand-product-count {
      font-size: 12px;
      color: #999;
      margin: 0 0 6px 0;
      font-weight: normal;

      .count-num {
        color: #e93323;
        font-weight: bold;
      }
    }

    .brand-auth-tag {
      display: inline-block;
      padding: 2px 8px;
      background-color: #e93323;
      color: #fff;
      font-size: 11px;
      border-radius: 2px;
      font-weight: normal;
    }
  }
}

.empty-state {
  text-align: center;
  padding: 80px 0;
  color: #999;
  font-size: 16px;
}
</style>