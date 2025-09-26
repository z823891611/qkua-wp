<div class="settings-page right-wrap" ref="settingsPage">
    <div class="settings-content w-h">
        <div class="user-avatar" :style="'background-image:url('+avatar+')'">
            <label for="avatar-input" class="editor-avatar">
                <i class="ri-camera-line"></i>
                <span>修改头像</span>
            </label>
            <input id="avatar-input" type="file" ref="avatarInput" accept="image/jpg,image/jpeg,image/png,image/gif" style="display: none;" @change="handleAvatarUpload">
        </div>
        <div class="user-info">
            <form @submit.stop.prevent="saveUserInfo">
                <div class="form-group">
                    <label for="nikename">昵称</label>
                    <input type="text" placeholder="请输入姓名" v-model="data.nickname">
                </div>
                <div class="form-group">
                    <label for="gender">性别</label>
                    <select v-model="data.sex">
                        <option value="1">男</option>
                        <option value="0">女</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">个性签名</label>
                    <textarea type="text" placeholder="请输入个性签名" v-model="data.description"></textarea>
                </div>
                <button type="submit" class="bg-text">保存</button>
            </form>
        </div>
    </div>
</div>