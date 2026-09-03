<template>
  <div class="news-detail">
    <div class="title wrapper_1200" v-if="newsDetail">
      <div class="home">
        <nuxt-link to="/">首页 > 新闻资讯 > </nuxt-link
        ><span class="news-title">{{ newsDetail.title }}</span>
      </div>
      <div class="content">
        <div class="title">{{ newsDetail.title }}</div>
        <div class="info">
          <div class="flex">
            <img class="icon" src="../assets/images/time.png" alt="" />
            {{ newsDetail.add_time }}
          </div>
          <div class="flex">
            <img class="icon" src="../assets/images/watch.png" alt="" />
            {{ newsDetail.visit }}
          </div>
        </div>
        <div class="content-text" v-html="newsDetail.content"></div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "news_detail",
  auth: false,
  data() {
    return {
      newsDetail: null
    };
  },
  // 监听路由变化

  watch: {
    $route: {
      handler: function(to, from) {
        this.getNewsDetail();
      },
      immediate: true
    }
  },
  async asyncData({ app, query }) {
    let [categoryMsg] = await Promise.all([
      //获取banner分类
      app.$axios.get(`pc/get_news_detail/${query.id}`)
    ]);
    return {
      newsDetail: categoryMsg.data
    };
  },
  fetch({ store }) {
    store.commit("isHeader", true);
    store.commit("isFooter", true);
  },
  head() {
    return {
      title: "新闻咨询-" + this.newsDetail.title
    };
  },
  methods: {
    goDetail(item) {
      let path = item.presale
        ? (path = {
            path: "/goods_presell_detail",
            query: {
              id: item.id
            }
          })
        : (path = { path: `/goods_detail/${item.id}` });
      this.$router.push(path);
    },
    getNewsDetail() {
      this.$axios
        .get(`pc/get_news_detail/${this.$route.query.id}`)
        .then(res => {
          this.newsDetail = res.data;
        })
        .catch(function(err) {
          this.$message.error(err);
        });
    }
  }
};
</script>

<style scoped lang="scss">
.home {
  padding: 10px 0;
  color: #333333;
  font-size: 14px;
  font-weight: 400;
  .news-title {
    color: #999999;
  }
}
.content {
  padding: 50px;
  background-color: #fff;
  .title {
    text-align: center;
    font-weight: 500;
    font-size: 24px;
    color: #333333;
    line-height: 24px;
    margin-bottom: 30px;
  }
  .info {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
    .flex {
      display: flex;
      align-items: center;
      margin-right: 30px;
      color: #999999;
      .icon {
        width: 16px;
        height: 16px;
        margin-right: 5px;
      }
    }
  }
}
</style>
