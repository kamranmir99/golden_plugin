import axios from "axios";

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
export const API = `${BACKEND_URL}/api`;

export const api = axios.create({ baseURL: API });

export const getCourses = () => api.get("/courses").then((r) => r.data);
export const getStudents = (params) => api.get("/students", { params }).then((r) => r.data);
export const getStats = (params) => api.get("/stats", { params }).then((r) => r.data);
export const getHotspots = (params) => api.get("/hotspots", { params }).then((r) => r.data);
export const getPluginInfo = () => api.get("/plugin-info").then((r) => r.data);
