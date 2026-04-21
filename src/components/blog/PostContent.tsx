import { useEffect, useMemo, useState } from "react";
import parse, { type HTMLReactParserOptions, type DOMNode, Element, domToReact } from "html-react-parser";
import DOMPurify from "dompurify";

const ALLOWED_TAGS = [
  "p", "h2", "h3", "h4", "ul", "ol", "li", "a", "strong", "em", "u", "s", "del", "sub", "sup",
  "blockquote", "img", "figure", "figcaption", "iframe", "table", "thead", "tbody", "tr", "td", "th",
  "code", "pre", "hr", "br", "div", "span", "details", "summary", "video",
];

const ALLOWED_ATTR = [
  "href", "title", "rel", "target", "src", "alt", "width", "height", "loading", "allow",
  "allowfullscreen", "srcset", "sizes", "class", "data-type", "data-youtube-video", "data-vimeo-video",
  "data-native-video", "data-aspect-width", "data-aspect-height", "frameborder", "style", "open",
  "controls", "autoplay", "loop", "muted", "playsinline", "poster",
];

const URI_PATTERN = /^(https?:\/\/|\/storage\/|\/)/i;

function readContentFromSource(): string {
  if (typeof document === "undefined") return "";
  const src = document.getElementById("post-content-html");
  if (!src) return "";
  // Support both <template> (legacy) and <div hidden> (current)
  if (src instanceof HTMLTemplateElement) return src.innerHTML;
  return src.innerHTML;
}

const parserOptions: HTMLReactParserOptions = {
  replace: (node) => {
    if (!(node instanceof Element)) return undefined;

    // Responsive wrapper for YouTube/Vimeo embeds
    if (node.name === "div" && (node.attribs["data-youtube-video"] !== undefined || node.attribs["data-vimeo-video"] !== undefined)) {
      return (
        <div className="my-6 overflow-hidden rounded-lg bg-black">
          <div className="relative w-full" style={{ aspectRatio: "16 / 9" }}>
            {domToReact(node.children as DOMNode[], parserOptions)}
          </div>
        </div>
      );
    }

    if (node.name === "iframe") {
      const src = node.attribs.src ?? "";
      return (
        <iframe
          src={src}
          title={node.attribs.title || "Embedded video"}
          allow={node.attribs.allow || "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"}
          allowFullScreen
          loading="lazy"
          className="absolute inset-0 h-full w-full border-0"
        />
      );
    }

    if (node.name === "img") {
      const src = node.attribs.src ?? "";
      const alt = node.attribs.alt ?? "";
      return (
        <img
          src={src}
          alt={alt}
          loading={node.attribs.loading === "eager" ? "eager" : "lazy"}
          decoding="async"
          className="my-6 h-auto w-full rounded-lg"
        />
      );
    }

    return undefined;
  },
};

export function PostContent() {
  const [html, setHtml] = useState<string>("");

  useEffect(() => {
    setHtml(readContentFromSource());
    // Remove the hidden source element after React has captured HTML
    // Prevents double-content on screen readers and duplicates in view-source
    const src = document.getElementById("post-content-html");
    src?.remove();
  }, []);

  const cleaned = useMemo(() => {
    if (!html) return "";
    return DOMPurify.sanitize(html, {
      ALLOWED_TAGS,
      ALLOWED_ATTR,
      ALLOWED_URI_REGEXP: URI_PATTERN,
      ADD_TAGS: ["iframe"],
    });
  }, [html]);

  if (!cleaned) return null;

  return <div className="post-content prose prose-invert prose-lg max-w-none">{parse(cleaned, parserOptions)}</div>;
}
