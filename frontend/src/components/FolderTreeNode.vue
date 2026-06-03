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
      </button>

      <button
        v-if="canManage"
        class="tree-action"
        type="button"
        aria-label="Xóa thư mục"
        @click.stop="$emit('remove', folder)"
      >
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div v-if="hasChildren && expanded" class="tree-children">
      <FolderTreeNode
        v-for="child in folder.descendants"
        :key="child.id"
        :folder="child"
        :selected-id="selectedId"
        :expanded-ids="expandedIds"
        :can-manage="canManage"
        @select="$emit('select', $event)"
        @toggle="$emit('toggle', $event)"
        @remove="$emit('remove', $event)"
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
  },
  computed: {
    hasChildren() {
      return Boolean(this.folder.descendants?.length);
    },
    expanded() {
      return this.expandedIds.includes(this.folder.id);
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
  padding: 6px 2px;
  text-align: left;
}

.tree-action {
  padding: 6px 8px;
  color: #707070;
  font-size: 0.7rem;
  opacity: 0;
}

.tree-row:hover .tree-action {
  opacity: 1;
}

.tree-children {
  margin-left: 14px;
  padding-left: 8px;
  border-left: 1px solid #dededb;
}
</style>
