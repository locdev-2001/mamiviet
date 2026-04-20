import React from "react";

const Loading: React.FC = () => {
  return (
    <div className="flex items-center justify-center w-full h-full py-8">
      <div className="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-gray-300"></div>
    </div>
  );
};

export default Loading; 