import { subscribeRealtimeData } from "@/services/realtimeService";

export default {
  mounted() {
    const scopes = this.$options.realtimeScopes || [];
    if (!scopes.length || typeof this.refreshRealtimeData !== "function") return;

    this._unsubscribeRealtimeRefresh = subscribeRealtimeData(scopes, async (payload) => {
      const hasLoadingState = Object.prototype.hasOwnProperty.call(this.$data, "loading");
      const previousLoading = this.loading;
      const refresh = this.refreshRealtimeData(payload);

      // Keep background refreshes from replacing the current screen with a skeleton.
      if (hasLoadingState) this.loading = previousLoading;

      try {
        await refresh;
      } catch {
        // Keep the current data when a background refresh temporarily fails.
      } finally {
        if (hasLoadingState) this.loading = previousLoading;
      }
    });
  },
  beforeUnmount() {
    this._unsubscribeRealtimeRefresh?.();
  },
};
