<template>
  <div class="tree-node">
    <div class="tree-row" :class="{ selected: selectedId === folder.id }">
      <button
        v-if="hasChildren"
        class="tree-toggle"
        type="button"
        :aria-label="expanded ? 'Thu gọn thư mục' : 'Mở rộng thư mục'"
        @click.stop="$emit('toggle', folder.id)"
      >
        <i class="fas" :class="expanded ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
      </button>
      <span v-else class="tree-toggle-placeholder"></span>

      <button class="tree-label" type="button" @click="$emit('select', folder)">
        <i class="fas me-2" :class="expanded ? 'fa-folder-open' : 'fa-folder'"></i>
        <span>{{ folder.name }}</span>
        <small class="document-count">{{ documentCount }}</small>
      </button>

      <div v-if="canManage" class="tree-menu">
        <button
          class="tree-action"
          type="button"
          aria-label="Tùy chọn thư mục"
          @click.stop="$emit('toggle-menu', folder.id)"
        >
          <i class="fas fa-ellipsis-h"></i>
        </button>

        <div v-if="isMenuOpen" class="tree-menu-popover" @click.stop>
          <button type="button" @click="$emit('add-child', folder)">
            <i class="fas fa-folder-plus me-2"></i>Thêm thư mục con
          </button>
          <button type="button" class="danger" @click="$emit('remove', folder)">
            <i class="fas fa-trash me-2"></i>Xóa thư mục
          </button>
        </div>
      </div>
    </div>

    <div v-if="hasChildren && expanded" class="tree-children">
      <FolderTreeNode
        v-for="child in folder.descendants"
        :key="child.id"
        :folder="child"
        :selected-id="selectedId"
        :expanded-ids="expandedIds"
        :can-manage="canManage"
        :open-menu-folder-id="openMenuFolderId"
        :document-counts="documentCounts"
        @select="$emit('select', $event)"
        @toggle="$emit('toggle', $event)"
        @remove="$emit('remove', $event)"
        @add-child="$emit('add-child', $event)"
        @toggle-menu="$emit('toggle-menu', $event)"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: "FolderTreeNode",
  props: {
    folder: { type: Object, required: true },
    selectedId: { type: String, default: null },
    expandedIds: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    openMenuFolderId: { type: String, default: null },
    documentCounts: { type: Object, required: true },
  },
  emits: ["select", "toggle", "remove", "add-child", "toggle-menu"],
  computed: {
    hasChildren() {
      return Boolean(this.folder.descendants?.length);
    },
    expanded() {
      return this.expandedIds.includes(this.folder.id);
    },
    isMenuOpen() {
      return this.openMenuFolderId === this.folder.id;
    },
    documentCount() {
      return this.documentCounts[this.folder.id] || 0;
    },
  },
};
</script>

<style scoped>
.tree-row {
  display: flex;
  min-height: 34px;
  align-items: center;
  gap: 2px;
  border-radius: 5px;
  position: relative;
}

.tree-row:hover,
.tree-row.selected {
  background: #f1f1ef;
}

.tree-toggle,
.tree-action,
.tree-label {
  border: 0;
  background: transparent;
  color: #292929;
}

.tree-toggle,
.tree-toggle-placeholder {
  width: 22px;
  flex: 0 0 22px;
  text-align: center;
}

.tree-toggle {
  font-size: 0.65rem;
}

.tree-label {
  display: flex;
  flex: 1;
  align-items: center;
  gap: 6px;
  padding: 6px 2px;
  text-align: left;
}

.tree-label span {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.document-count {
  display: inline-grid;
  min-width: 20px;
  height: 18px;
  place-items: center;
  margin-left: auto;
  padding: 0 6px;
  border-radius: 999px;
  background: #ededeb;
  color: #707070;
  font-size: 0.68rem;
  font-weight: 600;
}

.tree-menu {
  position: relative;
}

.tree-action {
  padding: 6px 8px;
  color: #707070;
  font-size: 0.8rem;
  opacity: 0;
}

.tree-row:hover .tree-action,
.tree-action:focus {
  opacity: 1;
}

.tree-menu-popover {
  position: absolute;
  right: 0;
  top: calc(100% + 4px);
  z-index: 20;
  display: grid;
  min-width: 170px;
  overflow: hidden;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.tree-menu-popover button {
  display: flex;
  align-items: center;
  padding: 9px 11px;
  border: 0;
  background: #fff;
  color: #292929;
  font-size: 0.82rem;
  text-align: left;
}

.tree-menu-popover button:hover {
  background: #f1f1ef;
}

.tree-menu-popover .danger {
  color: #b42318;
}

.tree-children {
  margin-left: 14px;
  padding-left: 8px;
  border-left: 1px solid #dededb;
}
</style>
