var qkMessagePage = new Vue({
    el:'.message-page',
    data:{
        data:'',
        index:'',
        list:[],
        paged:0,
        pages:0,
        locked:false,
        loadingMore: false,
        messageContent:'',
        selectedImage: null, //图片
        imageId:0, //图片ID
        paramValue:'',
        timeThreshold: 600, // 时间间隔的阈值，单位为秒，默认为10分钟
        scrollHeightBeforeLoad:0, //加载更多之前的高度
        noMore:false,
        showEmoji:false,
        emoji:[],
        clientWidth:qkClientWidth
    },
    created(){
        this.paramValue = qkGetQueryVariable('type') || qkGetQueryVariable('whisper')
    },
    mounted(){
        if(!qktoken) return this.$createModal('login');
        
        (/iphone|ipod|ipad/i.test(navigator.appVersion)) && document.addEventListener(
            'blur',
            event => {
        			// 当页面没出现滚动条时才执行，因为有滚动条时，不会出现这问题
                    // input textarea 标签才执行，因为 a 等标签也会触发 blur 事件
                if (
                    document.documentElement.offsetHeight <=
                    document.documentElement.clientHeight &&
                    ['input', 'textarea'].includes(event.target.localName)
                ) {
                    document.body.scrollIntoView() // 回顶部
                }
            },
            true
        )

        
        this.$http.post(qk_rest_url+'getContactList').then(res => {
            this.data = res.data
            var index = this.data.findIndex(item => item.type === this.paramValue || item.from?.id === this.paramValue);
            if(index != -1) {
                this.indexChange(index)
            }else if(!isNaN(this.paramValue) && this.paramValue && qkGetQueryVariable('whisper')) {
                this.$http.post(qk_rest_url+'getContact',{user_id:this.paramValue}).then(res => {
                    this.data.unshift(res.data); // 将指定元素添加到数组的开头
                    this.index = 0
                }).catch(err=>{
                    this.$message({ message: err.response.data.message, type: 'error' });
                })
            }
        }).catch(err => {
            this.$message({ message: err.response.data.message, type: 'warning' });
        });

        // 监听窗口大小变化的resize事件
        window.addEventListener('resize', ()=> {
            // 更新ClientWidth变量的值
            this.clientWidth = document.body.clientWidth;
        }); 

    },
    methods:{
        sendMessage() {
            if (this.messageContent.trim() === '' && !this.selectedImage) {
                return;
            }
            
            // 获取用户头像元素
            const userAvatarElement = document.querySelector('.user-avatar img');
            
            // 获取用户头像链接
            const userAvatarUrl = userAvatarElement.getAttribute('src');
            
            //当前时间
            function addZero(num) {
                return num < 10 ? "0" + num : num;
            }
        
            var now = new Date();
            var hours = addZero(now.getHours());
            var minutes = addZero(now.getMinutes());
            var time = hours + ':' + minutes;
        
            // 构造发送的数据
            var data = {
                from: {
                    avatar:userAvatarUrl
                },
                content: this.messageContent,
                is_self: true,
                is_read:false,
                date: time,
                mark:{}
            };
            
            if(this.emoji.length) {
                for (let i = 0; i < this.emoji.length; i++) {
                    const emojiName = Object.keys(this.emoji[i])[0];
                    const emojiIcon = this.emoji[i][emojiName];
                    const regex = new RegExp("\\[" + emojiName + "\\]", "g");
                    data.content = data.content.replace(regex, emojiIcon);
                }
            }
            
            if(this.selectedImage) {
                data.mark.url = this.selectedImage;
                data.mark.type = 'image';
                this.messageContent = '[图片]';
            }
            
            if(this.index > 0) {
                var item = this.data[this.index];
                item.content = this.messageContent;
                item.date = time;
                this.data.splice(this.index, 1);// 从数组中删除指定元素
                this.data.unshift(item); // 将指定元素添加到数组的开头
                this.index = 0
            }else {
                this.data[this.index].content = this.messageContent;
                this.data[this.index].date = time;
            }
            
            // 将发送的消息追加到消息列表中
            this.list.push(data); //.concat(this.list);
            
            this.$nextTick(()=>{
                lazyLoadInstance.update()
            })
            
            // 滚动到底部
            this.scrollToBottom();
            
            this.$http.post(qk_rest_url + 'sendMessage', {
                user_id:this.data[this.index].from.id,
                content:this.messageContent,
                image_id:this.imageId
            }).then(res => {
            }).catch(err => {
                this.$message({ message: err.response.data.message, type: 'warning' });
            });
            
            // 清空输入框内容
            this.messageContent = '';
            this.selectedImage = null;
            this.imageId = 0
        },
        handleScroll() {
            var messageList = this.$refs.messageList;
            if (this.data[this.index].type === 'chat' && messageList.scrollTop > 0 && messageList.scrollTop < 10 && !this.loadingMore) {
                messageList.removeEventListener('scroll', this.handleScroll);
                this.loadMore();
            }else if (this.data[this.index].type !== 'chat' && (messageList.scrollTop + messageList.offsetHeight) >= (messageList.scrollHeight - 300) && !this.loadingMore) {
                messageList.removeEventListener('scroll', this.handleScroll);
                this.loadMore();
            }
        },
        loadMore() {
            // 获取新消息前的滚动高度
            this.scrollHeightBeforeLoad = this.$refs.messageList.scrollHeight;

            this.loadingMore = true;
            this.getMessageList();
        },
        indexChange(index){
            if(index === this.index) return;
            this.list = []
            this.index = index
            this.paged = 0
            this.pages = 0
            this.loadingMore = false
            this.noMore = false
            this.locked = false;
            
            if(this.data[this.index].from.id) {
                qkSetQueryParams({
                    whisper:this.data[this.index].from.id 
                });
            }else {
                qkSetQueryParams({
                    type:this.data[this.index].type
                });
            }
            
            this.$nextTick(() => {
                this.$refs.messageList.removeEventListener('scroll', this.handleScroll);
            });
            
            this.getMessageList()
        },
        backClick() {
            this.list = []
            this.index = ''
            this.paged = 0
            this.pages = 0
            this.loadingMore = false
            this.noMore = false
            this.locked = false;
            // 获取当前地址栏的URL
            var url = new URL(window.location.href);
            // 清空URLSearchParams对象
            url.search = '';
            history.pushState(null, null, url);
        },
        getMessageList() {
            this.paged++
            if(this.paged > this.pages  && this.paged != 1) return;
            this.locked = true;
            
            var data = {
                type:this.data[this.index].type,
                id:this.data[this.index].id,
                sender_id:this.data[this.index].type == 'chat' ? this.data[this.index].from.id : 0,
                unread:this.data[this.index].unread,
                paged:this.paged
            }
            
            this.$http.post(qk_rest_url+'getMessageList',data).then(res => {

                if(this.data[this.index].type== 'chat' || this.data[this.index].type == 'vip' || this.data[this.index].type == 'circle' || this.data[this.index].type == 'distribution') {
                    this.list.unshift(...this.filteredList(res.data.data))
                }else {
                    this.list.push(...res.data.data)
                }
                // const selfMessage = Object.values(this.list).find(item => item.is_self === true);
                // if (selfMessage) {
                //     // 找到了is_self为true的对象
                //     console.log(selfMessage);
                // } else {
                //     // 没有找到is_self为true的对象
                //     console.log("没有找到自己的消息");
                // }
                
                if(this.paged > 1 && res.data.data.length > 0 && (this.data[this.index].type== 'chat' || this.data[this.index].type == 'vip' || this.data[this.index].type == 'circle' || this.data[this.index].type == 'distribution')) {
                    this.$nextTick(() => {
                        this.$refs.messageList.scrollTop = this.$refs.messageList.scrollHeight - this.scrollHeightBeforeLoad;
                        
                    });
                }
                
                this.pages = res.data.pages;
                this.locked = false;
                this.loadingMore = false;
                if(this.pages <= this.paged) {
                    this.noMore = true
                }else {
                    this.$nextTick(() => {
                        this.$refs.messageList.addEventListener('scroll', this.handleScroll);
                    });
                }
                
                if(this.paged == 1 && (this.data[this.index].type== 'chat' || this.data[this.index].type == 'vip' || this.data[this.index].type == 'circle' || this.data[this.index].type == 'distribution')) {
                    addLoadEvent(this.scrollToBottom())
                }
                
                this.$nextTick(()=>{
                    lazyLoadInstance.update()
                })
                
                imgFancybox('.img-msg img')
            })
        },
        handleFileUpload(event){
            if (event.target.files.length <= 0)return
            if(this.locked == true) return
            this.locked = true
            let file = event.target.files[0]
            //this.selectedImage = URL.createObjectURL(file)
            // 重置文件输入框的值，以便下一次选择文件
            event.target.value = '';
            
            if(file.size <= 1 * 1024000) {
                let formData = new FormData()
                formData.append('file', file, file.name)
                formData.append("post_id", 1)
                formData.append("type", 'post')
                
                this.$http.post(qk_rest_url + 'fileUpload',formData).then(res=>{
                    this.imageId = res.data.id
                    this.selectedImage = res.data.url
                    this.locked = false
                    this.sendMessage()
                    
                    
                }).catch(err=>{
                    let msg = err.response.data.message
                    msg = msg ? msg : '上传失败，请重新上传'
                    
                    this.$message({ message: msg, type: 'warning' });
                    this.selectedImage = null
                    this.imageId = 0
                    this.locked = false
                })
                
            }else {
                this.$message({ message: "文件[" + file.name + "]大小超过限制，最大1M，请重新选择", type: 'error' });
                this.locked = false
            }
        },
        handleClick(emoji) {
            this.messageContent +="["+ emoji.name +"]";
            this.emoji.push({[emoji.name]: '<img class="emoticon-image '+emoji.size+'" src="'+emoji.icon+'">'});
            this.$refs.textarea.focus()
        },
        scrollToBottom() {
            this.$nextTick(() => {
                var messageList = document.querySelector('.message-list');
                messageList.scrollTop = messageList.scrollHeight;
            });
        },
        filteredList(list) {
            
            let filteredList = [];
            let prevTime = null;
        
            for (let i = 0; i < list.length; i++) {
                let item = list[i];
                let currentTime = item.time;
        
                if (!prevTime || currentTime - prevTime > this.timeThreshold) {
                    // 时间间隔大于阈值，显示时间
                    item.showDate = true;
                } else {
                    item.showDate = false;
                }
        
                filteredList.push(item);
                prevTime = currentTime;
            }
        
            return filteredList;
        },
        containerStyle(mark) {
            const maxWidth = 320;
            const maxHeight = 320;
            const scale = Math.min(maxWidth / mark.width, maxHeight / mark.height);
            const width = mark.width * scale;
            const height = mark.height * scale;
        
            return {
                width: `${width}px`,
                height: `${height}px`
            };
        }
    }
})