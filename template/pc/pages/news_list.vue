<template>
  <div class="goods_cate">
    <div class="wrapper_1200 warpper">
      <div class="leftCon">
        <div class="nav">
          <div class="navCon acea-row row-between-wrapper">
            <div class="list acea-row row-middle">
              <div
                class="item"
                :class="current === index ? 'font-color' : ''"
                v-for="(item, index) in categoryList"
                :key="index"
                @click="category(index)"
              >
                {{ item.title }}
              </div>
            </div>
            <div class="moreCon" @mouseleave="leave()">
              <div
                class="more"
                :class="current === -1 ? 'font-color' : ''"
                @mouseenter="enter"
              >
                <span class="iconfont icon-gengduofenlei"></span>更多
              </div>
              <div class="moreCategory acea-row row-middle" v-if="seen">
                <div
                  class="item"
                  :class="moreCurrent === index ? 'font-color' : ''"
                  v-for="(item, index) in categoryList"
                  :key="index"
                  @click="moreItem(index)"
                >
                  {{ item.title }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="categoryCon" v-if="categoryCurrent.length">
          <div
            class="item"
            :class="{ on: conIndex == index }"
            v-for="(item, index) in categoryCurrent"
            :key="index"
            @click="categoryCon(index)"
          >
            {{ item.title }}
          </div>
        </div>
        <div
          class="goods acea-row row-middle"
          :class="{ pt20: !categoryCurrent.length }"
          v-if="newsList.length && !loading"
        >
          <div
            class="item"
            v-for="(item, index) in newsList"
            :key="index"
            @click="goDetail(item)"
          >
            <div class="pictrue"><img :src="item.image_input[0]" /></div>
            <div class="content acea-row">
              <div class="msg">
                <div class="title">{{ item.title }}</div>
                <div class="desc line2">{{ item.synopsis }}</div>
              </div>
              <div class="sort">
                <div class="time acea-row mr-20">
                  <img class="icon" src="../assets/images/time.png" alt="" />
                  <span>{{ item.add_time }}</span>
                </div>
                <div class="watch acea-row">
                  <img class="icon" src="../assets/images/watch.png" alt="" />
                  <span>{{ item.visit }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="!newsList.length && !loading" class="noGoods">
          <img src="../assets/images/noyue.png" alt="" />
          <div class="text">暂无新闻数据</div>
        </div>
        <div class="pagination" v-if="newsList.length">
          <el-pagination
            background
            layout="prev, pager, next"
            :total="newsCount"
            @current-change="getNewslist"
          >
          </el-pagination>
        </div>
      </div>
      <div class="hotNewsCon">
        <div class="title">热门资讯</div>
        <div class="hotNews">
          <div
            class="item"
            v-for="(item, index) in hotNewsList"
            :key="index"
            @click="goDetail(item)"
          >
            <div class="num">{{ index + 1 }}.</div>
            <div class="content">
              <div class="title line2">{{ item.title }}</div>
              <div class="desc">
                <img class="icon" src="../assets/images/time.png" alt="" />
                <span>{{ item.add_time }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "news_list",
  auth: false,
  data() {
    return {
      categoryList: [],
      hotNewsList: [],
      categoryCurrent: [],
      current: 0,
      moreCurrent: 0,
      seen: false,
      newsList: [],
      page: 1, //代表页面的初始页数
      limit: 10,
      newsCount: 0, //总页数
      title: "下拉加载更多",
      cid: 0, //一级分类
      conIndex: 0,
      loading: true,
    };
  },
  async asyncData({ app, query }) {
    let [categoryMsg] = await Promise.all([
      //获取banner分类
      app.$axios.get("pc/get_news_category"),
    ]);
    let category = categoryMsg.data;
    category.unshift({
      id: 0,
      title: "全部",
    });
    return {
      categoryList: category,
    };
  },
  fetch({ store }) {
    store.commit("isHeader", true);
    store.commit("isFooter", true);
  },
  head() {
    return {
      title: "商品分类-" + this.$store.state.titleCon,
    };
  },
  created() {
    this.getNewslist();
    this.getHotNews();
  },
  methods: {
    goDetail(item) {
      let path = {
        path: "/news_detail",
        query: {
          id: item.id,
        },
      };
      this.$router.push(path);
    },
    getHotNews() {
      let currentPage = {
        page: 1,
        limit: 10,
        cid: 0,
      };
      this.$axios
        .get("/pc/get_news_list", {
          params: currentPage,
        })
        .then((res) => {
          this.hotNewsList = res.data.list;
        })
        .catch(function (err) {
          this.$message.error(err);
        });
    },
    getNewslist(page = 1) {
      this.loading = true;
      let _this = this,
        currentPage = {
          page,
          limit: this.limit,
          cid: this.cid,
        };
      _this.$axios
        .get("/pc/get_news_list", {
          params: currentPage,
        })
        .then(function (res) {
          _this.newsCount = res.data.count;
          // 请求完成后，把得到的数据拼接到当前dom里面
          _this.newsList = _this.newsList.concat(res.data.list);
        })
        .catch(function (err) {
          _this.$message.error(err);
        })
        .finally(function () {
          _this.loading = false;
        });
    },
    category(index) {
      this.current = index;
      this.moreCurrent = index;
      this.categoryCurrent = this.categoryList[index].children || [];
      this.titleName = this.categoryList[index].title;
      this.newsList = [];
      this.page = 1;
      this.cid = this.categoryCurrent.length
        ? this.categoryCurrent[0].id
        : this.categoryList[index].id;
      this.getNewslist();
    },
    categoryCon(index) {
      this.newsList = [];
      this.page = 1;
      this.conIndex = index;
      this.cid = this.categoryCurrent[index].id;
      this.getNewslist();
    },
    enter() {
      this.current = -1;
      this.seen = true;
    },
    leave() {
      this.seen = false;
      this.current = this.moreCurrent;
    },
    moreItem(index) {
      this.moreCurrent = index;
      this.categoryCurrent = this.categoryList[index].children;
      this.titleName = this.categoryList[index].title;
      this.seen = false;
      this.current = index;
      this.newsList = [];
      this.page = 1;
      this.cid = this.categoryList[index].id;
      this.getNewslist();
    },
  },
};
</script>

<style scoped lang="scss">
.goods_cate {
  margin-top: 2px;
  .noGoods {
    text-align: center;
    .pictrue {
      width: 274px;
      height: 174px;
      margin: 130px auto 0 auto;
      img {
        width: 100%;
        height: 100%;
      }
    }
    .name {
      font-size: 14px;
      color: #969696;
      margin-top: 20px;
      margin-bottom: 290px;
    }
  }
  .categoryCon {
    display: flex;
    flex-wrap: wrap;
    padding: 0px 30px 20px 30px;
    background-color: #fff;
    .item {
      margin-right: 20px;
      margin-top: 20px;
      background: #f5f5f5;
      color: #999999;
      padding: 7px 10px;
      cursor: pointer;
    }
    .item.on {
      background: rgba(233, 51, 35, 0.1);
      color: #e93323;
    }
  }
  .nav {
    width: 100%;
    height: 77px;
    cursor: pointer;
    margin-top: 20px;
    .navCon {
      // border-bottom: 1px solid #eeeeee;
      background-color: #fff;
      &.wrapper_1200 {
        overflow: unset !important;
        background-color: #fff;
      }
      height: 100%;
      .moreCon {
        position: relative;
        .moreCategory {
          padding: 30px 30px 10px 30px;
          position: absolute;
          top: 76px;
          right: 0;
          width: 860px;
          background-color: #fff;
          box-shadow: 0px 2px 6px 0px rgba(0, 0, 0, 0.1);

          .item {
            margin: 0 40px 28px 0;

            &:hover {
              color: #e93323;
            }
          }
        }
      }
      .list {
        height: 100%;
        overflow: hidden;
        padding-left: 35px;

        .item {
          height: 100%;
          font-size: 16px;
          color: #333333;
          display: flex;
          align-items: center;
          & ~ .item {
            margin-left: 50px;
          }
          &:hover {
            color: #e93323;
          }
        }
      }
      .more {
        position: relative;
        height: 100%;
        line-height: 77px;
        width: 132px;
        text-align: center;
        &:before {
          position: absolute;
          content: " ";
          left: 0;
          top: 50%;
          margin-top: -8px;
          width: 1px;
          height: 16px;
          background-color: #efefef;
        }
        &.font-color {
          // border-bottom: 2px solid #e93323;
        }
        .iconfont {
          margin-right: 6px;
        }
      }
    }
  }
  .wrapper {
    background-color: #fff;
    padding: 25px 17px;
    cursor: pointer;
    .list {
      width: 1100px;
      border-bottom: 1px dotted #efefef;
      padding-bottom: 10px;
      .item {
        margin-right: 30px;
        margin-bottom: 10px;
        &:hover {
          color: #e93323;
        }
      }
    }
    .name {
      color: #969696;
      margin-right: 20px;
    }
  }
  .warpper {
    display: flex;
    min-height: 500px;
  }
  .leftCon {
    flex: 1;
    margin-right: 20px;
  }
  .goods {
    background-color: #fff;

    .item {
      padding: 0px 30px 20px 30px;
      width: 100%;
      cursor: pointer;
      display: flex;
      .pictrue {
        width: 200px;
        height: 132px;
        margin-right: 20px;
        img {
          width: 100%;
          height: 100%;
        }
      }
      .content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 10px 20px 10px 0;
        .msg {
          display: flex;
          flex-direction: column;
          .title {
            font-weight: 500;
            font-size: 16px;
            color: #333333;
            line-height: 16px;
            margin-bottom: 20px;
          }
          .desc {
            font-weight: 400;
            font-size: 14px;
            color: #666666;
            line-height: 22px;
          }
        }
        .sort {
          display: flex;
          font-weight: 400;
          font-size: 14px;
          color: #999999;
          line-height: 16px;
          .icon {
            width: 16px;
            height: 16px;
            margin-right: 6px;
          }
        }
      }
    }
  }
  .noGoods {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    height: 400px;
    background: #fff;
    img {
      width: 300px;
      margin-bottom: 20px;
    }
    .text {
      color: #999999;
    }
  }
  .hotNewsCon {
    width: 320px;
    height: max-content;
    background-color: #fff;
    margin-top: 20px;
    padding: 30px;
    .title {
      font-weight: 600;
      font-size: 18px;
      color: #333333;
      line-height: 18px;
      margin-bottom: 10px;
    }
    // 最后一个隐藏下划线
    .item:last-child {
      border-bottom: none;
    }
    .item {
      display: flex;
      padding: 20px 0;
      cursor: pointer;
      // 底部虚线
      border-bottom: 1px dotted #efefef;
      .num {
        font-weight: 600;
        font-size: 16px;
        color: #e93323;
        line-height: 20px;
        margin-right: 10px;
      }
      .content {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        .title {
          font-weight: 400;
          font-size: 16px;
          color: #333333;
          line-height: 20px;
        }
        .desc {
          display: flex;
          align-items: center;
          color: #999999;
          .icon {
            width: 16px;
            height: 16px;
            margin-right: 6px;
          }
        }
      }
    }
  }
  .pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    background-color: #fff;
    padding: 20px 0;
  }
}
.mr-20 {
  margin-right: 20px;
}
.pt20 {
  padding-top: 20px;
}
</style>
