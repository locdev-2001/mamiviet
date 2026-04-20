import React from "react";

const GlobalButton: React.FC = () => {
  return (
    <button
      onClick={() => alert('Emolyzer!')}
      className="fixed bottom-48 right-6 z-[101] w-[200px] rounded-[30px] bg-white border-1 border-black flex items-center justify-center"
      style={{ padding: 0 }}
      aria-label="Emolyzer"
    >
      <img src="logo-emolyzer.webp" alt="Emolyzer Logo" style={{ width: '100%', height: '100%', objectFit: 'contain', display: 'block' }} />
    </button>
  );
};

export default GlobalButton; 