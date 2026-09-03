<template>
  <div class="cssa">
    <div class="title">
      <div class="title-content">
        <div class="title-text">关于我们</div>
        <div>通过相关信息，了解我们更全面的我们</div>
      </div>
    </div>
    <div v-if="content" class="content" v-html="content"></div>
  </div>
</template>
<script>
export default {
  auth: false,
  data() {
    return {
      content: "",
    };
  },
  created() {
    this.getContent();
  },
  head() {
    return {
      title: "关于我们"
    }
  },
  methods: {
    getContent() {
      let that = this;
      that.$axios.get("/get_agreement/7").then((res) => {
        this.content = res.data.content;
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.cssa {
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  .title {
    width: 100%;
    height: 230px;
    background: url("~assets/images/about-us-banner.png") no-repeat;
    background-size: 100% 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    .title-content {
      width: 518px;
      height: 74px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      font-weight: 400;
      font-size: 15px;
      color: #ffffff;
      line-height: 15px;
      background-image: url("~assets/images/about-us-bg.png");
      background-size: 100% 100%;
      background-position: center;
      .title-text {
        font-weight: 600;
        font-size: 30px;
        color: #ffffff;
        line-height: 30px;
        margin-bottom: 10px;
      }
    }
  }
  .content {
    width: 1200px;
    max-width: 1200px;
    background-color: #fff;
    padding: 50px;
    margin-top: 20px;
  }
}
</style>
