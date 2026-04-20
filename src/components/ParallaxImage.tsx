import { motion, useScroll, useTransform } from 'framer-motion'
import { useRef } from 'react'

interface ParallaxImageProps {
  src: string
  alt: string
  className?: string
  direction?: 'up' | 'down'
  intensity?: number
}

export const ParallaxImage = ({ 
  src, 
  alt, 
  className = '',
  direction = 'up',
  intensity = 50
}: ParallaxImageProps) => {
  const ref = useRef<HTMLDivElement>(null)
  
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start end", "end start"]
  })

  // Transform scroll progress to Y movement - slower animation
  const yRange = direction === 'up' ? [intensity, -intensity] : [-intensity, intensity]
  const y = useTransform(scrollYProgress, [0.2, 0.8], yRange)

  return (
    <div ref={ref} className={`${className}`}>
      <motion.div 
        style={{ y }}
        className="w-full h-full"
      >
        <img 
          src={src} 
          alt={alt}
          className="w-full h-full object-cover"
        />
      </motion.div>
    </div>
  )
}