//index.js
//获取应用实例
const app = getApp()

Page({
  data: {
    motto: '',
    userInfo: {},
    hasUserInfo: false,
    canIUse: wx.canIUse('button.open-type.getUserInfo'),
    picUrl: '',
  },
  //事件处理函数
  onLoad: function () {
    // 显示当前页面的转发按钮
    wx.showShareMenu({
      withShareTicket: true,
      menus: ['shareAppMessage', 'shareTimeline']
    });
    this.getPicUrl();
    if (app.globalData.userInfo) {
      this.setData({
        userInfo: app.globalData.userInfo,
        hasUserInfo: true
      })
    } else if (this.data.canIUse) {
      // 由于 getUserInfo 是网络请求，可能会在 Page.onLoad 之后才返回
      // 所以此处加入 callback 以防止这种情况
      app.userInfoReadyCallback = res => {
        this.setData({
          userInfo: res.userInfo,
          hasUserInfo: true
        })
      }
    } else {
      // 在没有 open-type=getUserInfo 版本的兼容处理
      wx.getUserInfo({
        success: res => {
          app.globalData.userInfo = res.userInfo
          this.setData({
            userInfo: res.userInfo,
            hasUserInfo: true
          })
        }
      })
    }
  },
  getUserInfo: function (e) {
    if (e.detail.rawData) {
      app.globalData.userInfo = e.detail.userInfo
      this.setData({
        userInfo: e.detail.userInfo,
        hasUserInfo: true
      })
    }
    this.getPicUrl();
  },
  getPicUrl: function () {
    let self = this;
    wx.getSetting({
      success: function (res) {
        if (res.authSetting['scope.userInfo']) {
          // 已经授权，可以直接调用 getUserInfo 获取头像昵称
          wx.getUserInfo({
            success: function (res) {
              wx.showLoading({
                title: '加载中',
                mask: true,
                success: function(){
                  wx.request({
                    url: 'https://wx.eson.site/', //仅为示例，并非真实的接口地址
                    method: 'POST',
                    data: res.userInfo,
                    header: {
                      'content-type': 'application/json' // 默认值
                    },
                    success: function (res) {
                      console.log(res.data)
                      self.setData({
                        picUrl: res.data.pic,
                      })
                      self.data.picUrl = res.data.pic;
                      wx.hideLoading()
                    }
                  })
                },
                fail: function(){
                  wx.showToast({
                    title: '获取失败！',
                    duration: 2000
                  })
                }
              })
            }
          })
        }
      }
    })
  },
  downfile: function () {
    let self = this;
    wx.showLoading({
      title: '正在保存。。。',
      mask: true,
      success: function(){
        wx.downloadFile({
          url: self.data.picUrl,
          success: function (res) {
            console.log(res);

            wx.getSetting({
              success: function (res) {
                if (!res.authSetting['scope.writePhotosAlbum']) {
                  wx.hideLoading();
                  wx.showToast({
                    title: '请点击设置开启相册权限~',
                    icon: 'none',
                    duration: 2000
                  });
                  return false;
                }
              }
            })

            wx.saveImageToPhotosAlbum({
              filePath: res.tempFilePath,
              success: function (res) {
                wx.showToast({
                  title: '保存成功',
                  icon: 'success',
                  duration: 2000
                })
                console.log(res)
                wx.hideLoading();
              },
              fail: function (res) {
                console.log(res);
                wx.hideLoading();
                wx.showToast({
                  title: '取消保存！',
                  icon: 'none',
                  duration: 2000
                })
              }
            })
          },
          fail: function () {
            wx.hideLoading()
            console.log('fail')
          }
        })
      },
      fail: function(){
        wx.hideLoading()
        wx.showToast({
          title: '获取失败！',
          duration: 2000
        })
      }
    })
  },
  onShareAppMessage: function() {
    return {
      title: '北辰送您头像小国旗',
      imageUrl:'/static/FlagHead.png',
      path: '/pages/index/index',
      success: (data) => {
        console.log(data)
      }
    }
  },
  onShareTimeline: function(){
    return {
      title: '北辰送您头像小国旗',
      imageUrl:'/static/FlagHead.png',
      query: '/pages/index/index',
      success: (data) => {
        console.log(data)
      }
    }
  },
})
