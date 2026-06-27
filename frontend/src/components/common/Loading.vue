<template>
  <div class="skeleton-loader" :class="`is-${type}`" aria-hidden="true">
    <template v-if="type === 'cards'">
      <div class="row g-4">
        <div v-for="item in count" :key="item" class="col-md-6 col-lg-4">
          <div class="skeleton-card">
            <div class="skeleton-card-body">
              <div class="skeleton-line skeleton-title"></div>
              <div class="skeleton-badges">
                <span class="skeleton-pill"></span>
                <span class="skeleton-pill short"></span>
              </div>
              <div class="skeleton-line"></div>
              <div class="skeleton-line medium"></div>
              <div class="skeleton-tags">
                <span></span>
                <span></span>
                <span class="short"></span>
              </div>
            </div>
            <div class="skeleton-card-footer">
              <div>
                <div class="skeleton-line tiny"></div>
                <div class="skeleton-line tiny medium"></div>
                <div class="skeleton-line tiny short"></div>
              </div>
              <span class="skeleton-button"></span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="type === 'dashboard'">
      <div class="skeleton-stats">
        <div v-for="item in 4" :key="item" class="skeleton-stat">
          <span class="skeleton-icon"></span>
          <div>
            <div class="skeleton-line tiny short"></div>
            <div class="skeleton-line skeleton-number"></div>
          </div>
        </div>
      </div>
      <div class="skeleton-panel">
        <div class="skeleton-line skeleton-title"></div>
        <div class="skeleton-line medium"></div>
        <div class="skeleton-activity" v-for="item in 4" :key="item">
          <span class="skeleton-avatar"></span>
          <div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line tiny short"></div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="type === 'detail'">
      <div class="skeleton-detail">
        <section class="skeleton-panel large">
          <div class="skeleton-line skeleton-title"></div>
          <div class="skeleton-preview"></div>
          <div class="skeleton-line"></div>
          <div class="skeleton-line medium"></div>
          <div class="skeleton-grid-lines">
            <div v-for="item in 6" :key="item" class="skeleton-line tiny"></div>
          </div>
        </section>
        <aside class="skeleton-side">
          <div class="skeleton-panel small"></div>
          <div class="skeleton-panel small"></div>
        </aside>
      </div>
    </template>

    <template v-else-if="type === 'settings'">
      <div class="skeleton-settings">
        <aside class="skeleton-panel">
          <div v-for="item in 4" :key="item" class="skeleton-setting-tab">
            <span class="skeleton-icon small"></span>
            <span class="skeleton-line tiny"></span>
          </div>
        </aside>
        <section class="skeleton-panel">
          <div class="skeleton-line skeleton-title"></div>
          <div v-for="item in 5" :key="item" class="skeleton-line"></div>
        </section>
      </div>
    </template>

    <template v-else-if="type === 'folders'">
      <div class="skeleton-folders">
        <aside class="skeleton-panel">
          <div v-for="item in 8" :key="item" class="skeleton-line"></div>
        </aside>
        <section class="skeleton-panel">
          <div class="skeleton-line skeleton-title"></div>
          <div class="skeleton-subfolders">
            <span v-for="item in 4" :key="item"></span>
          </div>
          <div class="row g-4">
            <div v-for="item in 3" :key="item" class="col-md-6 col-lg-4">
              <div class="skeleton-card compact"></div>
            </div>
          </div>
        </section>
      </div>
    </template>

    <template v-else>
      <div class="skeleton-list">
        <div v-for="item in count" :key="item" class="skeleton-list-row">
          <span class="skeleton-avatar"></span>
          <div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line tiny medium"></div>
          </div>
          <span class="skeleton-button"></span>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
export default {
  name: "Loading",
  props: {
    type: {
      type: String,
      default: "list",
    },
    count: {
      type: Number,
      default: 6,
    },
  },
};
</script>

<style scoped>
.skeleton-loader {
  width: 100%;
}

.skeleton-card,
.skeleton-panel,
.skeleton-stat,
.skeleton-list-row {
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
}

.skeleton-card-body,
.skeleton-card-footer,
.skeleton-panel,
.skeleton-stat,
.skeleton-list-row {
  padding: 14px;
}

.skeleton-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-top: 1px solid #dededb;
  background: #f8f9fa;
}

.skeleton-line,
.skeleton-pill,
.skeleton-tags span,
.skeleton-button,
.skeleton-icon,
.skeleton-avatar,
.skeleton-preview,
.skeleton-subfolders span {
  display: block;
  border-radius: 999px;
  background: linear-gradient(90deg, #ededeb 25%, #f7f7f5 37%, #ededeb 63%);
  background-size: 400% 100%;
  animation: skeleton-shimmer 1.35s ease-in-out infinite;
}

.skeleton-line {
  width: 100%;
  height: 13px;
  margin-bottom: 12px;
}

.skeleton-title {
  width: 58%;
  height: 22px;
}

.skeleton-number {
  width: 42px;
  height: 28px;
  margin-bottom: 0;
}

.skeleton-line.medium {
  width: 72%;
}

.skeleton-line.short {
  width: 44%;
}

.skeleton-line.tiny {
  height: 10px;
  margin-bottom: 8px;
}

.skeleton-badges,
.skeleton-tags,
.skeleton-subfolders {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
}

.skeleton-pill {
  width: 78px;
  height: 22px;
}

.skeleton-pill.short,
.skeleton-tags .short {
  width: 54px;
}

.skeleton-tags span {
  width: 68px;
  height: 22px;
}

.skeleton-button {
  width: 82px;
  height: 32px;
  flex: 0 0 82px;
  border-radius: 7px;
}

.skeleton-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 28px;
}

.skeleton-stat {
  display: flex;
  align-items: center;
  gap: 16px;
}

.skeleton-icon {
  width: 54px;
  height: 54px;
  flex: 0 0 54px;
  border-radius: 8px;
}

.skeleton-icon.small {
  width: 18px;
  height: 18px;
  flex: 0 0 18px;
  border-radius: 5px;
}

.skeleton-setting-tab {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 46px;
  border-bottom: 1px solid #eeeeec;
}

.skeleton-setting-tab:last-child {
  border-bottom: 0;
}

.skeleton-setting-tab .skeleton-line {
  width: 52%;
  margin-bottom: 0;
}

.skeleton-panel {
  min-height: 160px;
}

.skeleton-activity,
.skeleton-list-row {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 14px;
}

.skeleton-activity {
  grid-template-columns: auto minmax(0, 1fr);
  margin-top: 20px;
}

.skeleton-avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
}

.skeleton-detail,
.skeleton-settings,
.skeleton-folders {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 24px;
}

.skeleton-settings {
  grid-template-columns: 280px minmax(0, 1fr);
}

.skeleton-folders {
  grid-template-columns: 260px minmax(0, 1fr);
  gap: 0;
  border: 1px solid #dededb;
  border-radius: 8px;
  overflow: hidden;
}

.skeleton-folders .skeleton-panel {
  border: 0;
  border-radius: 0;
}

.skeleton-folders aside {
  border-right: 1px solid #dededb !important;
  background: #fbfbfa;
}

.skeleton-preview {
  height: 180px;
  margin: 18px 0;
  border-radius: 8px;
}

.skeleton-grid-lines {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 28px;
  margin-top: 24px;
}

.skeleton-side {
  display: grid;
  gap: 18px;
  align-content: start;
}

.skeleton-panel.small {
  min-height: 120px;
}

.skeleton-panel.large {
  min-height: 420px;
}

.skeleton-card.compact {
  height: 190px;
}

.skeleton-subfolders span {
  width: 120px;
  height: 38px;
  border-radius: 7px;
}

.skeleton-list {
  display: grid;
  gap: 12px;
}

@keyframes skeleton-shimmer {
  0% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0 50%;
  }
}

:global(body.theme-dark) .skeleton-card,
:global(body.theme-dark) .skeleton-panel,
:global(body.theme-dark) .skeleton-stat,
:global(body.theme-dark) .skeleton-list-row {
  border-color: #3f3f46;
  background: #242428;
}

:global(body.theme-dark) .skeleton-card-footer,
:global(body.theme-dark) .skeleton-folders aside,
:global(body.theme-dark) .skeleton-setting-tab {
  border-color: #3f3f46 !important;
  background: #2f2f35;
}

:global(body.theme-dark) .skeleton-line,
:global(body.theme-dark) .skeleton-pill,
:global(body.theme-dark) .skeleton-tags span,
:global(body.theme-dark) .skeleton-button,
:global(body.theme-dark) .skeleton-icon,
:global(body.theme-dark) .skeleton-avatar,
:global(body.theme-dark) .skeleton-preview,
:global(body.theme-dark) .skeleton-subfolders span {
  background: linear-gradient(90deg, #323238 25%, #3d3d44 37%, #323238 63%);
  background-size: 400% 100%;
}

@media (max-width: 900px) {
  .skeleton-stats,
  .skeleton-detail,
  .skeleton-settings,
  .skeleton-folders {
    grid-template-columns: 1fr;
  }

  .skeleton-folders aside {
    border-right: 0 !important;
    border-bottom: 1px solid #dededb;
  }
}
</style>
