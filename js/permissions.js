export const Permissions = {
    data: {},
    can(group, element) {
        return !!(this.data && this.data[group] && this.data[group][element]);
    }
}