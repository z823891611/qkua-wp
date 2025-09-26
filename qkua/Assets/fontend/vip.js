var qkVipPage = new Vue({
    el:'.vip-body',
    data:{
        data:[],
        user:[],
        time: {
            hour: "00",
            minute: "00",
            second: "00",
            millisecond: "000"
        },
        countdownInterval: null
    },
    mounted() {
        if(this.$refs.vipPage){
            this.getVipInfo()
            this.countTime()
        }
    },
    methods:{
        calculateMinDiscount(data) {
            data.forEach(item => {
                let minPrice = Infinity;
                let minPriceElement = null;
                item.vip_group.forEach(element => {
                    const discountedPrice = (element.price * element.discount) / 100;
                    if (discountedPrice < minPrice) {
                        minPrice = discountedPrice;
                        minPriceElement = element;
                    }
                });
                
                item.vip = minPriceElement
            });
            
            return data;
        },
        getVipInfo(){
            this.$http.post(qk_rest_url+'getVipInfo').then(res=>{
                this.data = this.calculateMinDiscount(res.data.data)
                this.user = res.data.user_data
            })
        },
        vipPay(index){
            this.$createModal('vip',{
                size:720,
                open:(ev)=>{
                    ev.$refs.component.$data.index = index;
                },
            })
        },
        countTime() {
            // 获取倒计时的结束时间（从服务器获取的时间）
            //const endTime = new Date("2023-09-21 00:00:00").getTime();
            const endTime = new Date((new Date).setHours(23, 59, 59, 999)).getTime()
            
            // 判断当前时间是否已经超过结束时间
            if (new Date().getTime() > endTime) {
                return; // 如果已过期，则不执行定时器
            }
            
            // 更新倒计时的函数
            const updateCountdown = () => {
                const currentTime = new Date().getTime();
                const remainingTime = endTime - currentTime;
            
                // 如果剩余时间小于等于0，清除计时器，不再更新倒计时
                if (remainingTime <= 0) {
                    clearInterval(this.countdownInterval);
                    return;
                }
            
                // 计算剩余时间的小时、分钟、秒和毫秒
                this.time.hour = Math.floor(remainingTime / (1000 * 60 * 60));
                this.time.minute = this.prefixZero(Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60)));
                this.time.second = this.prefixZero(Math.floor((remainingTime % (1000 * 60)) / 1000));
                this.time.millisecond = this.prefixZero(Math.floor((remainingTime % 1000) / 1));
            };
            
            // 每隔10毫秒更新倒计时
            this.countdownInterval = setInterval(updateCountdown, 10);
        },
        prefixZero(number) {
            return number > 9 ? number : "0" + number;
        }
    }
    
})