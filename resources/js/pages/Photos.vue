<template>

    <div class="photo-picker">
        <h1>Фото галерея</h1>
        <div class="controls">
            <button @click="triggerFileInput" class="action-btn">
                <span>📁 Выбрать из галереи</span>
            </button>
            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                multiple
                @change="handleFiles"
                class="hidden-input"
            >
        </div>

        <!-- Превью выбранных фотографий -->
        <div class="gallery">
            <div
                v-for="(photo, index) in photos"
                :key="index"
                class="photo-card"
                @click="openFullscreen(photo)"
            >
                <img
                    :src="photo.url"
                    :alt="`Фото ${index + 1}`"
                    class="thumbnail"
                >
                <button @click.stop="removePhoto(index)" class="delete-btn">
                    &times;
                </button>
            </div>
        </div>

        <!-- Полноэкранный просмотр -->
        <div
            v-if="fullscreenImage"
            class="fullscreen-overlay"
            @click="fullscreenImage = null"
        >
            <img :src="fullscreenImage" class="fullscreen-img">
        </div>
    </div>
</template>

<script>
export default {
    name: 'PhotoPicker',
    data() {
        return {
            photos: [], // Массив объектов: { url: string, file: File }
            fullscreenImage: null
        }
    },
    methods: {
        triggerFileInput() {
            this.$refs.fileInput.click()
        },

        handleFiles(event) {
            const files = Array.from(event.target.files)
            if (!files.length) return

            files.forEach(file => {
                if (!file.type.match('image.*')) return

                const reader = new FileReader()
                reader.onload = (e) => {
                    this.photos.push({
                        url: e.target.result,
                        file: file
                    })
                }
                reader.readAsDataURL(file)
            })

            // Сбрасываем значение input, чтобы можно было выбирать те же файлы повторно
            event.target.value = ''
        },

        removePhoto(index) {
            this.photos.splice(index, 1)
        },

        openFullscreen(photo) {
            this.fullscreenImage = photo.url
        },

        // Можно добавить метод для получения всех выбранных файлов
        getSelectedFiles() {
            return this.photos.map(photo => photo.file)
        }
    }
}
</script>

<style scoped>
.photo-picker {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.controls {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.hidden-input {
    display: none;
}

.action-btn {
    padding: 10px 15px;
    background: #4285f4;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}

.action-btn:hover {
    background: #3367d6;
}

.gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.photo-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.thumbnail {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.delete-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 25px;
    height: 25px;
    background: rgba(255,0,0,0.7);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
}

.delete-btn:hover {
    background: rgba(255,0,0,0.9);
}

.fullscreen-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.fullscreen-img {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
}

@media (max-width: 600px) {
    .gallery {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}
</style>
