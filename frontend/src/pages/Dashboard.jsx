import { useEffect, useMemo, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { MapContainer, TileLayer, CircleMarker, Popup, GeoJSON, useMap } from "react-leaflet";
import MarkerClusterGroup from "react-leaflet-cluster";
import L from "leaflet";
import "leaflet.heat";
import { Button } from "../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select";
import { Slider } from "../components/ui/slider";
import { ToggleGroup, ToggleGroupItem } from "../components/ui/toggle-group";
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from "../components/ui/sheet";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../components/ui/table";
import { Popover, PopoverContent, PopoverTrigger } from "../components/ui/popover";
import { Calendar } from "../components/ui/calendar";
import { BarChart, Bar, XAxis, YAxis, Tooltip as RTooltip, ResponsiveContainer, Cell } from "recharts";
import { Globe2, ChevronLeft, ChevronRight, Download, Layers, MapPin, Flame, CalendarIcon, X } from "lucide-react";
import { format } from "date-fns";
import { getCourses, getStudents, getStats, getHotspots, getCountryStats } from "@/lib/api";
import { toast } from "sonner";

const CHOROPLETH = ["#0A2530", "#114659", "#166E87", "#0CB2CC", "#00E5FF"];

function gradeColor(g) {
  if (g >= 85) return CHOROPLETH[4];
  if (g >= 75) return CHOROPLETH[3];
  if (g >= 65) return CHOROPLETH[2];
  if (g >= 55) return CHOROPLETH[1];
  return CHOROPLETH[0];
}

function HeatLayer({ points, visible }) {
  const map = useMap();
  useEffect(() => {
    if (!visible || !points.length) return;
    const layer = L.heatLayer(points, {
      radius: 28, blur: 22, maxZoom: 10, minOpacity: 0.35,
      gradient: { 0.1: "#4A0080", 0.3: "#B20066", 0.5: "#E63900", 0.7: "#FFAB00", 1.0: "#FFEA00" },
    }).addTo(map);
    return () => { map.removeLayer(layer); };
  }, [map, points, visible]);
  return null;
}

function ChoroplethLayer({ geojson, countryStats, onCountryClick }) {
  const map = useMap();
  const layerRef = useRef(null);

  const statsByIso3 = useMemo(() => {
    const m = {};
    countryStats.forEach((s) => { m[s.iso3] = s; });
    return m;
  }, [countryStats]);

  useEffect(() => {
    if (!geojson) return;
    if (layerRef.current) {
      map.removeLayer(layerRef.current);
      layerRef.current = null;
    }
    const layer = L.geoJSON(geojson, {
      style: (feature) => {
        const iso3 = feature.properties.ISO_A3;
        const s = statsByIso3[iso3];
        if (!s) return { fillColor: "#1C202A", fillOpacity: 0.15, color: "#282D3D", weight: 0.5 };
        return {
          fillColor: gradeColor(s.avg_grade),
          fillOpacity: 0.78,
          color: "#0A0C10",
          weight: 0.6,
        };
      },
      onEachFeature: (feature, lyr) => {
        const iso3 = feature.properties.ISO_A3;
        const name = feature.properties.ADMIN || feature.properties.NAME;
        const s = statsByIso3[iso3];
        if (s) {
          lyr.bindTooltip(
            `<div style="font-family:'IBM Plex Sans';color:#fff"><div style="font-weight:700">${name}</div><div style="font-family:'IBM Plex Mono';font-size:11px;color:#A1A8B4">${s.students} students · avg ${s.avg_grade}%</div></div>`,
            { sticky: true, className: "golden-tooltip" }
          );
          lyr.on({
            mouseover: (e) => e.target.setStyle({ weight: 2, color: "#00E5FF" }),
            mouseout: (e) => layer.resetStyle(e.target),
            click: () => onCountryClick({ iso3, name, ...s }),
          });
        }
      },
    }).addTo(map);
    layerRef.current = layer;
    return () => { if (layerRef.current) map.removeLayer(layerRef.current); };
  }, [geojson, statsByIso3, map, onCountryClick]);

  return null;
}

export default function Dashboard() {
  const [courses, setCourses] = useState([]);
  const [courseId, setCourseId] = useState("all");
  const [mode, setMode] = useState("cluster");
  const [gradeRange, setGradeRange] = useState([0, 100]);
  const [dateRange, setDateRange] = useState({ from: undefined, to: undefined });
  const [students, setStudents] = useState([]);
  const [stats, setStats] = useState(null);
  const [heat, setHeat] = useState([]);
  const [countryStats, setCountryStats] = useState([]);
  const [geojson, setGeojson] = useState(null);
  const [rightOpen, setRightOpen] = useState(true);
  const [loading, setLoading] = useState(true);
  const [drillCountry, setDrillCountry] = useState(null);
  const [drillStudents, setDrillStudents] = useState([]);

  const activeCourseId = courseId === "all" ? undefined : courseId;
  const dateParams = useMemo(() => ({
    last_access_from: dateRange.from ? format(dateRange.from, "yyyy-MM-dd") : undefined,
    last_access_to: dateRange.to ? format(dateRange.to, "yyyy-MM-dd") : undefined,
  }), [dateRange]);

  useEffect(() => { getCourses().then(setCourses).catch(() => toast.error("Failed to load courses")); }, []);

  useEffect(() => {
    fetch("/countries.geojson").then((r) => r.json()).then(setGeojson).catch(() => toast.error("Failed to load country polygons"));
  }, []);

  useEffect(() => {
    setLoading(true);
    const params = { course_id: activeCourseId, min_grade: gradeRange[0], max_grade: gradeRange[1], ...dateParams };
    Promise.all([
      getStudents(params),
      getStats({ course_id: activeCourseId, ...dateParams }),
      getHotspots({ course_id: activeCourseId, ...dateParams }),
      getCountryStats({ course_id: activeCourseId, ...dateParams }),
    ])
      .then(([s, st, h, cs]) => { setStudents(s); setStats(st); setHeat(h.points); setCountryStats(cs); })
      .catch(() => toast.error("Failed to load map data"))
      .finally(() => setLoading(false));
  }, [courseId, gradeRange, dateRange]); // eslint-disable-line

  const activeCourse = courses.find((c) => c.id === courseId);

  const openCountry = async (countryInfo) => {
    setDrillCountry(countryInfo);
    try {
      const list = await getStudents({
        course_id: activeCourseId,
        country_iso3: countryInfo.iso3,
        min_grade: gradeRange[0], max_grade: gradeRange[1],
        ...dateParams,
      });
      setDrillStudents(list);
    } catch {
      toast.error("Failed to load country detail");
    }
  };

  const exportCSV = () => {
    const header = "id,first_name,last_name,email,country,city,lat,lng,grade,last_access\n";
    const rows = students.map((s) => [s.id, s.first_name, s.last_name, s.email, s.country, s.city, s.lat, s.lng, s.effective_grade, s.last_access].join(",")).join("\n");
    const blob = new Blob([header + rows], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url; a.download = "golden_export.csv"; a.click(); URL.revokeObjectURL(url);
    toast.success(`Exported ${students.length} students`);
  };

  const distributionData = stats?.grade_distribution || [];
  const topCountriesData = stats?.top_countries || [];

  return (
    <div className="relative w-screen h-screen overflow-hidden bg-[#0F1115]" data-testid="dashboard-root">
      <MapContainer center={[20, 10]} zoom={2} minZoom={2} worldCopyJump zoomControl={false} className="absolute inset-0 z-0" data-testid="map-container">
        <TileLayer url="https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png" attribution='&copy; OpenStreetMap &copy; CARTO' subdomains="abcd" />

        {mode === "choropleth" && (
          <ChoroplethLayer geojson={geojson} countryStats={countryStats} onCountryClick={openCountry} />
        )}

        <TileLayer url="https://{s}.basemaps.cartocdn.com/dark_only_labels/{z}/{x}/{y}{r}.png" subdomains="abcd" opacity={0.85} />

        {mode === "cluster" && (
          <MarkerClusterGroup chunkedLoading maxClusterRadius={60}>
            {students.map((s) => (
              <CircleMarker key={s.id} center={[s.lat, s.lng]} radius={5} pathOptions={{ color: "#FFAB00", fillColor: "#FFAB00", fillOpacity: 0.85, weight: 1 }}>
                <Popup><StudentPopup s={s} activeCourse={activeCourse} /></Popup>
              </CircleMarker>
            ))}
          </MarkerClusterGroup>
        )}

        <HeatLayer points={heat} visible={mode === "hotspot"} />
      </MapContainer>

      {/* TOP NAV */}
      <nav className="absolute top-0 left-0 right-0 z-[1000] h-16 glass-panel border-0 border-b flex items-center justify-between px-6" data-testid="dashboard-nav">
        <Link to="/" className="flex items-center gap-2" data-testid="dashboard-logo">
          <div className="w-8 h-8 rounded-md bg-[#00E5FF] flex items-center justify-center">
            <Globe2 className="w-5 h-5 text-black" strokeWidth={2.5} />
          </div>
          <div className="leading-tight">
            <div className="font-heading font-black text-lg tracking-tighter">GOLDEN</div>
            <div className="font-mono text-[9px] text-[#666F7F] uppercase tracking-[0.15em]">
              {loading ? "Loading…" : `${students.length} learners · ${stats?.countries_covered || 0} countries`}
            </div>
          </div>
        </Link>
        <div className="flex items-center gap-2">
          <Button variant="outline" className="h-9 px-3 bg-[#1C202A] text-white border-[#282D3D] hover:bg-[#282D3D]" onClick={exportCSV} data-testid="export-csv-btn">
            <Download className="w-4 h-4 mr-1.5" /> Export CSV
          </Button>
          <Button variant="outline" className="h-9 w-9 p-0 bg-[#1C202A] text-white border-[#282D3D] hover:bg-[#282D3D]" onClick={() => setRightOpen((v) => !v)} data-testid="toggle-analytics-btn">
            {rightOpen ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
          </Button>
        </div>
      </nav>

      {/* LEFT SIDEBAR */}
      <aside className="absolute top-20 left-4 bottom-4 z-[400] w-80 flex flex-col gap-4 pointer-events-none overflow-y-auto" data-testid="left-sidebar">
        <div className="glass-panel rounded-lg p-5 pointer-events-auto" data-testid="kpi-panel">
          <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#666F7F] mb-3">// Key metrics</div>
          <div className="grid grid-cols-2 gap-4">
            <Kpi label="Students" value={stats?.total_students ?? "—"} testid="kpi-students" />
            <Kpi label="Countries" value={stats?.countries_covered ?? "—"} testid="kpi-countries" />
            <Kpi label="Avg. grade" value={stats?.avg_grade != null ? stats.avg_grade.toFixed(1) : "—"} accent testid="kpi-avg-grade" />
            <Kpi label="Courses" value={stats?.active_courses ?? "—"} testid="kpi-courses" />
          </div>
        </div>

        <div className="glass-panel rounded-lg p-5 pointer-events-auto" data-testid="filter-panel">
          <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#666F7F] mb-3">// Filters</div>

          <label className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#A1A8B4] mb-1.5 block">Course</label>
          <Select value={courseId} onValueChange={setCourseId}>
            <SelectTrigger className="bg-[#0A0C10] border-[#282D3D] text-white h-10" data-testid="course-select">
              <SelectValue placeholder="All courses" />
            </SelectTrigger>
            <SelectContent className="bg-[#14171F] border-[#282D3D] text-white z-[1200]">
              <SelectItem value="all" data-testid="course-opt-all">All courses</SelectItem>
              {courses.map((c) => (
                <SelectItem key={c.id} value={c.id} data-testid={`course-opt-${c.code}`}>
                  <span className="font-mono text-xs text-[#00E5FF] mr-2">{c.code}</span>{c.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <label className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#A1A8B4] mb-1.5 block mt-4">Last access</label>
          <Popover>
            <PopoverTrigger asChild>
              <Button variant="outline" className="w-full justify-start bg-[#0A0C10] border-[#282D3D] hover:bg-[#14171F] text-white font-normal h-10" data-testid="date-range-btn">
                <CalendarIcon className="w-3.5 h-3.5 mr-2 text-[#A1A8B4]" />
                {dateRange.from ? (
                  dateRange.to ? <span className="font-mono text-xs">{format(dateRange.from, "LLL d")} – {format(dateRange.to, "LLL d, y")}</span>
                  : <span className="font-mono text-xs">{format(dateRange.from, "LLL d, y")}</span>
                ) : <span className="text-[#A1A8B4] text-sm">Any time</span>}
                {(dateRange.from || dateRange.to) && (
                  <X className="w-3.5 h-3.5 ml-auto text-[#A1A8B4] hover:text-white" onClick={(e) => { e.stopPropagation(); setDateRange({ from: undefined, to: undefined }); }} data-testid="date-range-clear" />
                )}
              </Button>
            </PopoverTrigger>
            <PopoverContent className="z-[1200] bg-[#14171F] border-[#282D3D] p-0 text-white">
              <Calendar mode="range" selected={dateRange} onSelect={(r) => setDateRange(r || { from: undefined, to: undefined })} numberOfMonths={2} className="bg-[#14171F] text-white" />
            </PopoverContent>
          </Popover>

          <label className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#A1A8B4] mb-1.5 block mt-4">Visualization</label>
          <ToggleGroup type="single" value={mode} onValueChange={(v) => v && setMode(v)} className="bg-[#0A0C10] p-1 rounded-md border border-[#282D3D] grid grid-cols-3 gap-1" data-testid="mode-toggle">
            <ToggleGroupItem value="cluster" className="data-[state=on]:bg-[#282D3D] data-[state=on]:text-white text-[#A1A8B4] text-xs h-8" data-testid="mode-cluster"><MapPin className="w-3.5 h-3.5 mr-1" /> Cluster</ToggleGroupItem>
            <ToggleGroupItem value="choropleth" className="data-[state=on]:bg-[#282D3D] data-[state=on]:text-white text-[#A1A8B4] text-xs h-8" data-testid="mode-choropleth"><Layers className="w-3.5 h-3.5 mr-1" /> Grade</ToggleGroupItem>
            <ToggleGroupItem value="hotspot" className="data-[state=on]:bg-[#282D3D] data-[state=on]:text-white text-[#A1A8B4] text-xs h-8" data-testid="mode-hotspot"><Flame className="w-3.5 h-3.5 mr-1" /> Hotspot</ToggleGroupItem>
          </ToggleGroup>

          <div className="mt-4">
            <div className="flex justify-between items-center mb-2">
              <label className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#A1A8B4]">Grade range</label>
              <div className="font-mono text-xs text-white" data-testid="grade-range-value">{gradeRange[0]}–{gradeRange[1]}</div>
            </div>
            <Slider min={0} max={100} step={1} value={gradeRange} onValueChange={setGradeRange} className="py-2" data-testid="grade-range-slider" />
          </div>
        </div>

        <div className="glass-panel rounded-lg p-4 pointer-events-auto" data-testid="legend-panel">
          <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#666F7F] mb-2">// Legend · {mode}</div>
          {mode === "cluster" && <div className="flex items-center gap-2"><span className="w-3 h-3 rounded-full bg-[#FFAB00] inline-block" /><span className="text-xs text-[#A1A8B4]">Enrolled student (clustered)</span></div>}
          {mode === "choropleth" && (
            <div>
              <div className="flex h-2.5 rounded-sm overflow-hidden mb-1">{CHOROPLETH.map((c, i) => <div key={i} className="flex-1" style={{ background: c }} />)}</div>
              <div className="flex justify-between font-mono text-[10px] text-[#A1A8B4]"><span>&lt;55</span><span>65</span><span>75</span><span>85+</span></div>
              <div className="text-[10px] text-[#666F7F] mt-1.5">Click any country to drill in →</div>
            </div>
          )}
          {mode === "hotspot" && (
            <div>
              <div className="h-2.5 rounded-sm mb-1" style={{ background: "linear-gradient(to right, #4A0080, #B20066, #E63900, #FFAB00, #FFEA00)" }} />
              <div className="flex justify-between font-mono text-[10px] text-[#A1A8B4]"><span>low need</span><span>high support need</span></div>
            </div>
          )}
        </div>
      </aside>

      {/* RIGHT SIDEBAR */}
      <aside className={`absolute top-20 right-4 bottom-4 z-[400] w-80 flex flex-col gap-4 pointer-events-none transition-transform duration-300 ${rightOpen ? "translate-x-0" : "translate-x-[110%]"}`} data-testid="right-sidebar">
        <div className="glass-panel rounded-lg p-5 pointer-events-auto flex-1 overflow-auto">
          <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#666F7F] mb-3">// Top countries</div>
          <div className="space-y-1.5">
            {topCountriesData.map((c, i) => {
              const cs = countryStats.find((x) => x.country === c.country);
              return (
                <button
                  key={i}
                  onClick={() => cs && openCountry({ iso3: cs.iso3, name: c.country, students: c.students, avg_grade: c.avg_grade })}
                  className="w-full flex items-center justify-between text-sm hover:bg-[#282D3D] rounded px-2 py-1 transition-colors text-left text-white"
                  data-testid={`top-country-${i}`}
                >
                  <span className="truncate">{c.country}</span>
                  <div className="flex items-center gap-3">
                    <span className="font-mono text-xs text-[#00E5FF]">{c.students}</span>
                    <span className="font-mono text-xs text-[#A1A8B4]">{c.avg_grade}</span>
                  </div>
                </button>
              );
            })}
            {!topCountriesData.length && <div className="text-xs text-[#666F7F]">No data</div>}
          </div>
        </div>

        <div className="glass-panel rounded-lg p-5 pointer-events-auto" data-testid="distribution-panel">
          <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#666F7F] mb-3">// Grade distribution</div>
          <div className="h-40">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={distributionData} margin={{ top: 5, right: 0, bottom: 0, left: -25 }}>
                <XAxis dataKey="range" tick={{ fill: "#666F7F", fontSize: 9, fontFamily: "IBM Plex Mono" }} axisLine={{ stroke: "#282D3D" }} tickLine={false} />
                <YAxis tick={{ fill: "#666F7F", fontSize: 9, fontFamily: "IBM Plex Mono" }} axisLine={{ stroke: "#282D3D" }} tickLine={false} />
                <RTooltip contentStyle={{ background: "#14171F", border: "1px solid #282D3D", borderRadius: 6, fontSize: 11 }} cursor={{ fill: "#282D3D" }} />
                <Bar dataKey="count" radius={[2, 2, 0, 0]}>{distributionData.map((_, i) => (<Cell key={i} fill={CHOROPLETH[Math.min(CHOROPLETH.length - 1, Math.floor(i / 2))]} />))}</Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </aside>

      {/* DRILL-THROUGH SHEET */}
      <Sheet open={!!drillCountry} onOpenChange={(o) => !o && setDrillCountry(null)}>
        <SheetContent side="right" className="bg-[#0A0C10] border-l border-[#282D3D] text-white w-full sm:max-w-2xl z-[1500]" data-testid="drill-sheet">
          <SheetHeader>
            <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#00E5FF] mb-1">// Country detail</div>
            <SheetTitle className="font-heading text-2xl text-white tracking-tight" data-testid="drill-country-title">{drillCountry?.name}</SheetTitle>
            <SheetDescription className="font-mono text-xs text-[#A1A8B4]">
              {drillStudents.length} students · avg {drillCountry?.avg_grade}% · ISO {drillCountry?.iso3}
            </SheetDescription>
          </SheetHeader>
          <div className="mt-6 max-h-[calc(100vh-180px)] overflow-auto rounded-md border border-[#282D3D]">
            <Table>
              <TableHeader>
                <TableRow className="bg-[#14171F] hover:bg-[#14171F] border-[#282D3D]">
                  <TableHead className="text-[#A1A8B4] font-mono text-[10px] uppercase tracking-wider">Name</TableHead>
                  <TableHead className="text-[#A1A8B4] font-mono text-[10px] uppercase tracking-wider">City</TableHead>
                  <TableHead className="text-[#A1A8B4] font-mono text-[10px] uppercase tracking-wider">IP</TableHead>
                  <TableHead className="text-[#A1A8B4] font-mono text-[10px] uppercase tracking-wider text-right">Grade</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {drillStudents.map((s) => (
                  <TableRow key={s.id} className="border-[#282D3D] hover:bg-[#14171F]" data-testid={`drill-row-${s.id}`}>
                    <TableCell className="font-medium">{s.first_name} {s.last_name}</TableCell>
                    <TableCell className="text-[#A1A8B4]">{s.city}</TableCell>
                    <TableCell className="font-mono text-xs text-[#A1A8B4]">{s.ip}</TableCell>
                    <TableCell className="text-right font-mono font-semibold" style={{ color: gradeColor(s.effective_grade) }}>
                      {s.effective_grade}%
                    </TableCell>
                  </TableRow>
                ))}
                {!drillStudents.length && (
                  <TableRow><TableCell colSpan={4} className="text-center text-[#666F7F] py-8">No students match the current filters.</TableCell></TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </SheetContent>
      </Sheet>
    </div>
  );
}

function Kpi({ label, value, accent, testid }) {
  return (
    <div data-testid={testid}>
      <div className="font-mono text-[9px] uppercase tracking-[0.15em] text-[#666F7F] mb-1">{label}</div>
      <div className={`font-mono font-bold text-2xl tracking-tight ${accent ? "text-[#00E5FF]" : "text-white"}`}>{value}</div>
    </div>
  );
}

function StudentPopup({ s, activeCourse }) {
  return (
    <div className="min-w-[220px] text-white font-body">
      <div className="font-heading font-bold text-base mb-1">{s.first_name} {s.last_name}</div>
      <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#A1A8B4] mb-2">{s.city} · {s.country}</div>
      <div className="space-y-1 text-xs">
        <Row k="IP" v={s.ip} />
        <Row k="Email" v={s.email} />
        <Row k="Enrolled" v={`${s.enrolled_courses.length} courses`} />
        <Row k={activeCourse ? `${activeCourse.code} grade` : "Overall"} v={`${s.effective_grade}%`} accent />
      </div>
    </div>
  );
}
function Row({ k, v, accent }) {
  return (
    <div className="flex justify-between gap-3">
      <span className="text-[#A1A8B4]">{k}</span>
      <span className={`font-mono ${accent ? "text-[#00E5FF] font-semibold" : "text-white"}`}>{v}</span>
    </div>
  );
}
