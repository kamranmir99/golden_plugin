import { Link } from "react-router-dom";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { Globe2, Download, MapPin, Activity, Users, Layers, ChevronRight, Github } from "lucide-react";

const HERO_BG = "https://static.prod-images.emergentagent.com/jobs/8e7c4ad4-d560-446b-b304-b06014738a53/images/deef9fbdc566da2ff200f525c329e19700344066d01a0be1afa979b6ba645046.png";
const SECTION_BG = "https://static.prod-images.emergentagent.com/jobs/8e7c4ad4-d560-446b-b304-b06014738a53/images/36fb9b21f2983c524184c1e50b56bbad9034f088cb0f0ced97cb73f248c8e964.png";

const Feature = ({ icon: Icon, title, desc, testid }) => (
  <div data-testid={testid} className="glass-panel rounded-lg p-6 transition-transform hover:-translate-y-1 duration-300">
    <div className="w-10 h-10 rounded-md bg-[#00E5FF]/10 border border-[#00E5FF]/30 flex items-center justify-center mb-4">
      <Icon className="w-5 h-5 text-[#00E5FF]" strokeWidth={1.5} />
    </div>
    <h3 className="font-heading text-xl font-bold mb-2 tracking-tight">{title}</h3>
    <p className="text-sm text-[#A1A8B4] leading-relaxed">{desc}</p>
  </div>
);

export default function Landing() {
  return (
    <div className="min-h-screen bg-[#0A0C10] text-white overflow-x-hidden">
      {/* Nav */}
      <nav className="fixed top-0 left-0 right-0 z-50 h-16 glass-panel border-0 border-b px-6 flex items-center justify-between" data-testid="landing-nav">
        <Link to="/" className="flex items-center gap-2" data-testid="landing-logo">
          <div className="w-8 h-8 rounded-md bg-[#00E5FF] flex items-center justify-center">
            <Globe2 className="w-5 h-5 text-black" strokeWidth={2.5} />
          </div>
          <div className="leading-tight">
            <div className="font-heading font-black text-lg tracking-tighter">GOLDEN</div>
            <div className="font-mono text-[9px] text-[#666F7F] uppercase tracking-[0.15em]">Geospatial · Distance Education</div>
          </div>
        </Link>
        <div className="flex items-center gap-3">
          <a href="#features" className="hidden sm:block text-sm text-[#A1A8B4] hover:text-white transition-colors">Features</a>
          <a href="#install" className="hidden sm:block text-sm text-[#A1A8B4] hover:text-white transition-colors">Install</a>
          <Link to="/dashboard" data-testid="launch-dashboard-nav-btn">
            <Button className="bg-[#00E5FF] text-black hover:bg-[#33EFFF] font-semibold rounded-md h-9 px-4">
              Launch Dashboard <ChevronRight className="w-4 h-4 ml-1" />
            </Button>
          </Link>
        </div>
      </nav>

      {/* Hero */}
      <section className="relative pt-32 pb-24 px-6">
        <div className="absolute inset-0 opacity-50" style={{ backgroundImage: `url(${HERO_BG})`, backgroundSize: "cover", backgroundPosition: "center" }} />
        <div className="absolute inset-0 bg-gradient-to-b from-[#0A0C10]/60 via-[#0A0C10]/80 to-[#0A0C10]" />
        <div className="absolute inset-0 grid-bg opacity-40" />
        <div className="relative max-w-6xl mx-auto">
          <Badge variant="outline" className="border-[#00E5FF]/40 bg-[#00E5FF]/5 text-[#00E5FF] font-mono text-xs tracking-widest uppercase mb-6" data-testid="hero-badge">
            Moodle 3.8+ Local Plugin · v2026.02.07
          </Badge>
          <h1 className="font-heading font-black text-5xl sm:text-6xl lg:text-7xl tracking-[-0.04em] leading-[0.95] max-w-4xl mb-6" data-testid="hero-title">
            See every learner<br />
            <span className="text-[#00E5FF]">on one map.</span>
          </h1>
          <p className="text-lg text-[#A1A8B4] max-w-2xl leading-relaxed mb-8" data-testid="hero-subtitle">
            GOLDEN turns your Moodle site into a WebGIS. It geolocates users from the <span className="font-mono text-white">lastip</span> column with a local MaxMind GeoLite2 database and renders <span className="text-white">cluster</span>, <span className="text-white">choropleth</span> and <span className="text-white">hotspot</span> maps — per course or across your whole institution.
          </p>
          <div className="flex flex-wrap gap-3">
            <Link to="/dashboard" data-testid="hero-launch-btn">
              <Button className="bg-[#00E5FF] text-black hover:bg-[#33EFFF] font-semibold h-12 px-6 rounded-md">
                Launch Live Demo <ChevronRight className="w-4 h-4 ml-1" />
              </Button>
            </Link>
            <a href="/local_golden.zip" download data-testid="hero-download-btn">
              <Button variant="outline" className="h-12 px-6 rounded-md bg-[#1C202A] text-white border-[#282D3D] hover:bg-[#282D3D]">
                <Download className="w-4 h-4 mr-2" /> Download Plugin (.zip)
              </Button>
            </a>
          </div>

          <div className="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-3xl">
            {[
              { k: "Map modes", v: "3" },
              { k: "Countries supported", v: "global" },
              { k: "Geolocation", v: "local" },
              { k: "Admin only", v: "yes" },
            ].map((s, i) => (
              <div key={i} data-testid={`hero-stat-${i}`}>
                <div className="font-mono text-[10px] uppercase tracking-[0.15em] text-[#666F7F] mb-1">{s.k}</div>
                <div className="font-heading font-bold text-2xl tracking-tight">{s.v}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features */}
      <section id="features" className="relative py-24 px-6">
        <div className="max-w-6xl mx-auto">
          <div className="font-mono text-xs uppercase tracking-[0.2em] text-[#00E5FF] mb-3">// What it does</div>
          <h2 className="font-heading font-black text-3xl sm:text-4xl tracking-tight mb-12 max-w-2xl">Built for administrators who think in pixels, polygons and percentiles.</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Feature icon={MapPin} title="Cluster map" desc="Every enrolled user as a marker, clustered at zoom-out for instant scale comprehension. Click a cluster to fly-in." testid="feature-cluster" />
            <Feature icon={Layers} title="Choropleth by grade" desc="Thematic colouring of countries (or regions) by average course performance — spot under-served cohorts at a glance." testid="feature-choropleth" />
            <Feature icon={Activity} title="Hotspot analysis" desc="Kernel-density heatmap weighted by inverse grade to surface regions where students need academic support." testid="feature-hotspot" />
            <Feature icon={Users} title="Per-course filter" desc="Scope any visualization to a single course, a grade band, or the entire institution." testid="feature-filter" />
            <Feature icon={Globe2} title="Local GeoIP" desc="Uses MaxMind GeoLite2-City on your own server. No external API calls, no PII leaves your Moodle." testid="feature-geoip" />
            <Feature icon={Download} title="Drop-in install" desc="Standard Moodle local plugin. Unzip under /local/golden, run upgrade, done. Admin-capability gated." testid="feature-install" />
          </div>
        </div>
      </section>

      {/* Install */}
      <section id="install" className="relative py-24 px-6 border-t border-[#282D3D]">
        <div className="absolute inset-0 opacity-30" style={{ backgroundImage: `url(${SECTION_BG})`, backgroundSize: "cover", backgroundPosition: "center" }} />
        <div className="absolute inset-0 bg-gradient-to-b from-[#0A0C10] via-transparent to-[#0A0C10]" />
        <div className="relative max-w-4xl mx-auto">
          <div className="font-mono text-xs uppercase tracking-[0.2em] text-[#FFAB00] mb-3">// Installation</div>
          <h2 className="font-heading font-black text-3xl sm:text-4xl tracking-tight mb-8">Four steps to live.</h2>
          <div className="space-y-4">
            {[
              { n: "01", t: "Download the plugin ZIP", d: "Grab local_golden.zip from the button above or your Moodle plugin directory listing." },
              { n: "02", t: "Upload via Site admin → Plugins → Install plugins", d: "Moodle will unpack it into /local/golden and run the upgrade script." },
              { n: "03", t: "Place GeoLite2-City.mmdb", d: "Register free at maxmind.com, download GeoLite2-City.mmdb and drop it in /local/golden/data/ (or set the path in plugin settings)." },
              { n: "04", t: "Visit Site admin → Reports → GOLDEN Map", d: "Requires the local/golden:view capability — granted to the Manager role by default." },
            ].map((s, i) => (
              <div key={i} className="glass-panel rounded-lg p-5 flex gap-5" data-testid={`install-step-${i}`}>
                <div className="font-mono font-bold text-2xl text-[#00E5FF] leading-none">{s.n}</div>
                <div>
                  <div className="font-heading font-bold text-lg mb-1">{s.t}</div>
                  <div className="text-sm text-[#A1A8B4] leading-relaxed">{s.d}</div>
                </div>
              </div>
            ))}
          </div>
          <div className="mt-8 flex flex-wrap gap-3">
            <a href="/local_golden.zip" download data-testid="install-download-btn">
              <Button className="bg-[#00E5FF] text-black hover:bg-[#33EFFF] font-semibold h-11 px-5">
                <Download className="w-4 h-4 mr-2" /> local_golden.zip
              </Button>
            </a>
            <Link to="/dashboard" data-testid="install-demo-btn">
              <Button variant="outline" className="h-11 px-5 bg-[#1C202A] border-[#282D3D] hover:bg-[#282D3D]">
                Try the live demo <ChevronRight className="w-4 h-4 ml-1" />
              </Button>
            </Link>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-[#282D3D] py-10 px-6" data-testid="landing-footer">
        <div className="max-w-6xl mx-auto grid sm:grid-cols-2 gap-6 items-start">
          <div>
            <div className="font-mono text-[10px] uppercase tracking-[0.2em] text-[#666F7F] mb-2">// Maintained by</div>
            <div className="font-heading font-bold text-base text-white" data-testid="contributor-name">Kamran Mir</div>
            <div className="text-xs text-[#A1A8B4] leading-relaxed mt-1">
              Institute of Geographical Information Systems (IGIS)<br />
              National University of Sciences and Technology (NUST)<br />
              Islamabad, Pakistan
            </div>
            <a
              href="mailto:kmir.phd21igis@student.nust.edu.pk"
              className="font-mono text-xs text-[#00E5FF] hover:text-[#33EFFF] transition-colors mt-2 inline-block"
              data-testid="contributor-email"
            >
              kmir.phd21igis@student.nust.edu.pk
            </a>
          </div>
          <div className="sm:text-right">
            <div className="font-mono text-xs text-[#666F7F] uppercase tracking-[0.15em] mb-2">
              GOLDEN · Geospatial mOdeL for Distance EducatioN
            </div>
            <div className="flex sm:justify-end items-center gap-3 text-xs text-[#666F7F]">
              <span className="font-mono">Moodle 3.8+</span>
              <span>·</span>
              <span className="font-mono">PHP 7.1+</span>
              <span>·</span>
              <span className="font-mono">GPL v3</span>
            </div>
            <a href="#" className="text-xs text-[#666F7F] hover:text-white transition-colors inline-flex items-center gap-1 mt-2">
              <Github className="w-3 h-3" /> Source
            </a>
          </div>
        </div>
      </footer>
    </div>
  );
}
