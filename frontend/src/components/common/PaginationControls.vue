<template>
  <div v-if="totalPages > 1" class="pagination-shell">
    <div class="pagination-summary">
      Hiển thị {{ startItem }}-{{ endItem }} / {{ total }} mục
    </div>

    <div class="pagination-actions">
      <button type="button" :disabled="page <= 1" @click="changePage(page - 1)">
        <i class="fas fa-chevron-left"></i>
      </button>

      <button
        v-for="pageNumber in pageNumbers"
        :key="pageNumber"
        type="button"
        :class="{ active: pageNumber === page }"
        @click="changePage(pageNumber)"
      >
        {{ pageNumber }}
      </button>

      <button type="button" :disabled="page >= totalPages" @click="changePage(page + 1)">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: "PaginationControls",
  props: {
    page: {
      type: Number,
      required: true,
    },
    perPage: {
      type: Number,
      default: 15,
    },
    total: {
      type: Number,
      required: true,
    },
    scrollOnChange: {
      type: Boolean,
      default: true,
    },
  },
  emits: ["update:page"],
  computed: {
    totalPages() {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
    startItem() {
      return (this.page - 1) * this.perPage + 1;
    },
    endItem() {
      return Math.min(this.page * this.perPage, this.total);
    },
    pageNumbers() {
      const pages = [];
      const start = Math.max(1, this.page - 2);
      const end = Math.min(this.totalPages, start + 4);

      for (let page = start; page <= end; page += 1) {
        pages.push(page);
      }

      return pages;
    },
  },
  methods: {
    changePage(page) {
      if (page < 1 || page > this.totalPages || page === this.page) return;
      this.$emit("update:page", page);
      if (this.scrollOnChange) {
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    },
  },
};
</script>

<style scoped>
.pagination-shell {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-top: 22px;
}

.pagination-summary {
  color: #707070;
  font-size: 0.85rem;
}

.pagination-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pagination-actions button {
  display: grid;
  min-width: 36px;
  height: 36px;
  place-items: center;
  border: 1px solid #dededb;
  border-radius: 6px;
  background: #fff;
  color: #292929;
  font-weight: 700;
}

.pagination-actions button:hover:not(:disabled),
.pagination-actions button.active {
  border-color: #171717;
  background: #171717;
  color: #fff;
}

.pagination-actions button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

@media (max-width: 650px) {
  .pagination-shell {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
