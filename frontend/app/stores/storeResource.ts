
export const useStoreResource = defineStore("resource", {
  state: () => ({
    success: true,
    message: "",
    resource: {
      data: [],
      pagination: {
        totalItems: 0,
        currentPage: 1,
        itemsPerPage: 15,
        totalPages: 1,
        urlPath: "",
        urlQuery: null,
        nextPage: null,
        prevPage: null,
      },
      search: null,
      sort: {
        column: "",
        dir: "asc",
      },
      filters: {
        applied: null,
        available: null,
      },
      schema: null,
      columns: [],
      notifications: null,
      error: {
        code: "",
        details: [],
      },
    },
    isLoading: false,
    hasError: false,
  }),
  actions: {
    async fetchData(params) {
      this.isLoading = true;
      this.hasError = false;
      this.resource.error = { code: "", details: [] };
      try {
        const response = await useApiService().get(this.resource.pagination.urlPath, { params });
        const { data, pagination, search, sort, filters, schema, columns, notifications, message } = response;
        this.resource.data = data;
        this.resource.pagination = pagination;
        this.resource.search = search;
        this.resource.sort = sort;
        this.resource.filters = filters;
        this.resource.schema = schema;
        this.resource.columns = columns;
        this.resource.notifications = notifications;
        this.success = true;
        this.message = message;
      } catch (error) {
        this.hasError = true;
        this.resource.error = {
          code: error.code || "UNKNOWN_ERROR",
          details: error.details || ["An unexpected error occurred."],
        };
        useNotifyService().error(error.message || "An error occurred while fetching data.");
      } finally {
        this.isLoading = false;
      }
    },
    setSort(column, dir) {
      this.resource.sort = { column, dir };
    },
    setFilters(filters) {
      this.resource.filters.applied = filters;
    },
    setPagination(page, perPage) {
      this.resource.pagination.currentPage = page;
      this.resource.pagination.itemsPerPage = perPage;
    },
    setSearch(search) {
      this.resource.search = search;
    },
    setColumns(columns) {
      this.resource.columns = columns;
    },
    setSchema(schema) {
      this.resource.schema = schema;
    },
    setNotifications(notifications) {
      this.resource.notifications = notifications;
    },
    setError(error) {
      this.resource.error = error;
      this.hasError = !!error;
    },
    setMessage(message) {
      this.message = message;
    },
  },
});
