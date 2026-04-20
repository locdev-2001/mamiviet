import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import heroSushi from "@/assets/hero-sushi.jpg";

export function HeroSection() {
  return (
    <section className="relative min-h-[80vh] bg-gradient-hero overflow-hidden">
      <div className="absolute inset-0 bg-gradient-to-br from-background/20 to-background/60" />
      
      <div className="relative container mx-auto px-4 py-20">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* Left Content */}
          <div className="space-y-8">
            <div className="space-y-4">
              <p className="text-accent font-medium uppercase tracking-wider">
                Somon Special
              </p>
              <h1 className="text-6xl lg:text-7xl font-bold text-foreground">
                ALL YOU <br />
                <span className="text-primary">CAN EAT</span>
              </h1>
              <p className="text-sm text-muted-foreground uppercase tracking-wide">
                IM SHIZOO STORE <br />
                BOGENHAUSEN
              </p>
            </div>

            <div className="flex items-center space-x-4">
              <div className="text-center">
                <span className="text-3xl font-bold text-accent">42</span>
                <span className="text-lg text-muted-foreground">,-</span>
              </div>
              <div className="text-sm text-muted-foreground">
                <p>PRO PERSON*</p>
                <p className="text-xs">
                  täglich von 17:00 - 23:30 Uhr für 2 Std. Stunden
                </p>
              </div>
            </div>

            <Button variant="hero" size="lg" className="shadow-glow">
              Order Now
            </Button>
          </div>

          {/* Right Content - Image */}
          <div className="relative">
            <div className="relative rounded-2xl overflow-hidden shadow-elegant">
              <img
                src={heroSushi}
                alt="Fresh Sushi Selection"
                className="w-full h-[600px] object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-background/40 to-transparent" />
            </div>
            
            {/* Floating Price Card */}
            <Card className="absolute -bottom-6 -left-6 p-6 bg-card/90 backdrop-blur-sm border-border shadow-elegant">
              <p className="text-sm text-muted-foreground mb-2">Feinstes Sushi</p>
              <p className="text-lg font-semibold text-accent">für München</p>
            </Card>
          </div>
        </div>
      </div>
    </section>
  );
}