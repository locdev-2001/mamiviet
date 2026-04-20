# 🎰 Spin Wheel API Documentation for React Integration

## Base URL
```
https://your-domain.com/api/user/rewards
```

## Authentication
All endpoints require Bearer token authentication.

```javascript
// Headers
{
  'Authorization': 'Bearer ' + token,
  'Accept': 'application/json',
  'Content-Type': 'application/json'
}
```

---

## 🎯 API Endpoints

### 1. Get Rewards List
**Endpoint:** `GET /api/user/rewards`
**Purpose:** Lấy danh sách tất cả rewards để hiển thị trên wheel

#### Request
```javascript
fetch('/api/user/rewards', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
})
```

#### Success Response (200)
```json
{
  "data": [
    {
      "id": 1,
      "name": "10% Rabatt auf die nächste Bestellung",
      "probability": 25.5,
      "color": "#ff6b6b",
      "coupon": {
        "id": 1,
        "name": "10% Discount",
        "code": "SAVE10",
        "type": "percentage",
        "value": 10.0,
        "max_uses": 100
      }
    },
    {
      "id": 2,
      "name": "€5 Rabatt auf die nächste Bestellung", 
      "probability": 15.0,
      "color": "#4ecdc4",
      "coupon": {
        "id": 2,
        "name": "5 Euro Off",
        "code": "SAVE5EUR",
        "type": "fixed",
        "value": 5.0,
        "max_uses": null
      }
    }
  ]
}
```

#### Error Response (500)
```json
{
  "success": false,
  "message": "System error occurred. Please try again later.",
  "data": []
}
```

---

### 2. Get Remaining Attempts
**Endpoint:** `GET /api/user/rewards/remaining-attempts`
**Purpose:** Lấy số lượt quay còn lại của user

#### Request
```javascript
fetch('/api/user/rewards/remaining-attempts', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
})
```

#### Success Response (200)
```json
{
  "success": true,
  "remaining_attempts": 3,
  "user_id": 123
}
```

#### Error Response - Unauthorized (401)
```json
{
  "success": false,
  "message": "User account not found."
}
```

#### Error Response - System Error (500)
```json
{
  "success": false,
  "message": "System error occurred. Please try again later.",
  "remaining_attempts": 0
}
```

---

### 3. Spin Wheel
**Endpoint:** `GET /api/user/rewards/spin`
**Purpose:** Thực hiện quay wheel và nhận kết quả

#### Request
```javascript
fetch('/api/user/rewards/spin', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
})
```

#### Success Response - Won Reward (200)
```json
{
  "success": true,
  "message": "Herzlichen Glückwunsch! Sie haben gewonnen: 10% Rabatt Gutschein \"10% Discount\" wurde Ihrem Konto hinzugefügt.",
  "coupon_assigned": true,
  "remaining_attempts": 2,
  "reward": {
    "id": 1,
    "name": "10% Rabatt auf die nächste Bestellung",
    "probability": 25.5,
    "color": "#ff6b6b",
    "coupon": {
      "id": 1,
      "name": "10% Discount",
      "code": "SAVE10",
      "type": "percentage",
      "value": 10.0,
      "max_uses": 99
    }
  },
  "coupon_quantity": 1
}
```

#### Success Response - No Reward Won (200)
```json
{
  "success": true,
  "message": "Mehr Glück beim nächsten Mal! Keine Belohnung gewonnen.",
  "coupon_assigned": false,
  "remaining_attempts": 1,
  "reward": null
}
```

#### Error Response - No Attempts Left (403)
```json
{
  "success": false,
  "message": "Sie haben keine Drehversuche mehr übrig!",
  "can_spin": false,
  "remaining_attempts": 0
}
```

#### Error Response - No Rewards Available (422)
```json
{
  "success": false,
  "message": "Derzeit sind keine Belohnungen verfügbar.",
  "can_spin": true,
  "remaining_attempts": 3
}
```

#### Error Response - Unauthorized (401)
```json
{
  "success": false,
  "message": "Sie müssen angemeldet sein, um zu drehen.",
  "can_spin": false,
  "remaining_attempts": 0
}
```

#### Error Response - System Error (500)
```json
{
  "success": false,
  "message": "Systemfehler aufgetreten. Bitte versuchen Sie es später erneut.",
  "can_spin": true,
  "remaining_attempts": 2
}
```

---

## 🔧 React Integration Examples

### 1. React Hook for Spin Wheel
```javascript
import { useState, useEffect } from 'react';

const useSpinWheel = (token) => {
  const [rewards, setRewards] = useState([]);
  const [loading, setLoading] = useState(false);
  const [spinning, setSpinning] = useState(false);
  const [remainingAttempts, setRemainingAttempts] = useState(0);

  const headers = {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  };

  // Load rewards list
  const loadRewards = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/user/rewards', {
        method: 'GET',
        headers
      });

      const data = await response.json();
      
      if (response.ok) {
        setRewards(data.data || []);
      } else {
        throw new Error(data.message || 'Failed to load rewards');
      }
    } catch (error) {
      console.error('Error loading rewards:', error);
      throw error;
    } finally {
      setLoading(false);
    }
  };

  // Load remaining attempts
  const loadRemainingAttempts = async () => {
    try {
      const response = await fetch('/api/user/rewards/remaining-attempts', {
        method: 'GET',
        headers
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        setRemainingAttempts(data.remaining_attempts || 0);
      } else {
        console.error('Failed to load remaining attempts:', data.message);
        setRemainingAttempts(0);
      }
    } catch (error) {
      console.error('Error loading remaining attempts:', error);
      setRemainingAttempts(0);
    }
  };

  // Perform spin
  const performSpin = async () => {
    setSpinning(true);
    try {
      const response = await fetch('/api/user/rewards/spin', {
        method: 'GET',
        headers
      });

      const data = await response.json();
      setRemainingAttempts(data.remaining_attempts || 0);

      if (response.ok && data.success) {
        return {
          success: true,
          message: data.message,
          reward: data.reward,
          couponAssigned: data.coupon_assigned,
          couponQuantity: data.coupon_quantity,
          remainingAttempts: data.remaining_attempts
        };
      } else {
        return {
          success: false,
          message: data.message,
          canSpin: data.can_spin,
          remainingAttempts: data.remaining_attempts
        };
      }
    } catch (error) {
      console.error('Error spinning wheel:', error);
      return {
        success: false,
        message: 'Network error occurred',
        canSpin: true,
        remainingAttempts
      };
    } finally {
      setSpinning(false);
    }
  };

  useEffect(() => {
    if (token) {
      loadRewards();
      loadRemainingAttempts();
    }
  }, [token]);

  return {
    rewards,
    loading,
    spinning,
    remainingAttempts,
    loadRewards,
    loadRemainingAttempts,
    performSpin
  };
};

export default useSpinWheel;
```

### 2. SpinWheel Component
```javascript
import React, { useState } from 'react';
import useSpinWheel from './hooks/useSpinWheel';

const SpinWheel = ({ token, onResult }) => {
  const {
    rewards,
    loading,
    spinning,
    remainingAttempts,
    loadRemainingAttempts,
    performSpin
  } = useSpinWheel(token);

  const [message, setMessage] = useState('');
  const [wonReward, setWonReward] = useState(null);

  const handleSpin = async () => {
    if (spinning || remainingAttempts <= 0) return;

    const result = await performSpin();
    
    setMessage(result.message);
    
    if (result.success && result.reward) {
      setWonReward(result.reward);
      onResult?.(result);
    }
  };

  if (loading) {
    return <div className="spin-wheel-loading">Loading rewards...</div>;
  }

  return (
    <div className="spin-wheel-container">
      {/* Wheel Display */}
      <div className="wheel">
        {rewards.map((reward, index) => (
          <div 
            key={reward.id}
            className="wheel-segment"
            style={{ 
              backgroundColor: reward.color,
              transform: `rotate(${(360 / rewards.length) * index}deg)`
            }}
          >
            <span>{reward.name}</span>
          </div>
        ))}
      </div>

      {/* Spin Button */}
      <button 
        className={`spin-button ${spinning ? 'spinning' : ''}`}
        onClick={handleSpin}
        disabled={spinning || remainingAttempts <= 0}
      >
        {spinning ? 'Spinning...' : 'SPIN'}
      </button>

      {/* Attempts Counter */}
      <div className="attempts-counter">
        Remaining attempts: {remainingAttempts}
      </div>

      {/* Result Message */}
      {message && (
        <div className={`result-message ${wonReward ? 'success' : 'info'}`}>
          {message}
        </div>
      )}

      {/* Won Reward Display */}
      {wonReward && (
        <div className="won-reward">
          <h3>🎉 Congratulations!</h3>
          <p>You won: {wonReward.name}</p>
          {wonReward.coupon && (
            <div className="coupon-info">
              <p>Coupon: {wonReward.coupon.name}</p>
              <p>Code: {wonReward.coupon.code}</p>
              <p>
                Value: {wonReward.coupon.type === 'percentage' 
                  ? `${wonReward.coupon.value}%` 
                  : `€${wonReward.coupon.value}`}
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default SpinWheel;
```

### 3. Error Handling Utility
```javascript
export const handleSpinError = (error, response) => {
  // Log error for debugging
  console.error('Spin Error:', error, response);

  // Return user-friendly message based on status
  if (!response) {
    return 'Network connection failed. Please check your internet.';
  }

  switch (response.status) {
    case 401:
      return 'Please log in to spin the wheel.';
    case 403:
      return 'You have no spin attempts left!';
    case 422:
      return 'No rewards are currently available.';
    case 500:
      return 'Server error occurred. Please try again later.';
    default:
      return response.data?.message || 'An unexpected error occurred.';
  }
};
```

### 4. Usage in App Component
```javascript
import React from 'react';
import SpinWheel from './components/SpinWheel';
import { handleSpinError } from './utils/errorHandlers';

const App = () => {
  const token = localStorage.getItem('authToken');

  const handleSpinResult = (result) => {
    if (result.success && result.couponAssigned) {
      // Update user's coupon list
      console.log('New coupon added:', result.reward.coupon);
      // Show success notification
      showNotification('Coupon added to your account!', 'success');
    }
  };

  const showNotification = (message, type) => {
    // Your notification logic here
    console.log(`${type}: ${message}`);
  };

  return (
    <div className="app">
      <h1>Lucky Wheel</h1>
      <SpinWheel 
        token={token}
        onResult={handleSpinResult}
      />
    </div>
  );
};

export default App;
```

---

## 📋 Response Field Descriptions

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Indicates if the operation was successful |
| `message` | string | Localized message for user display |
| `reward` | object\|null | Won reward details (null if no reward) |
| `coupon_assigned` | boolean | Whether a coupon was assigned to user |
| `coupon_quantity` | integer | Number of this coupon user now has |
| `remaining_attempts` | integer | Number of spins left for user |
| `can_spin` | boolean | Whether user is allowed to spin |

## 🎨 Reward Object Structure

```typescript
interface Reward {
  id: number;
  name: string;
  probability: number; // 0-100
  color: string; // HEX color for wheel segment
  coupon: {
    id: number;
    name: string;
    code: string;
    type: 'percentage' | 'fixed';
    value: number;
    max_uses: number | null; // null = unlimited
  } | null;
}
```

## 🔒 Security Notes

1. **Always include Authorization header** with valid Bearer token
2. **Validate responses** before using data
3. **Handle network errors** gracefully
4. **Rate limiting** may apply - respect `remaining_attempts`
5. **Log errors** for debugging but don't expose sensitive info to users

## 🚀 Best Practices

1. **Cache rewards list** - only refetch when necessary
2. **Check remaining attempts** before showing spin button
3. **Show loading states** during API calls
4. **Disable spin button** while spinning or no attempts left
5. **Handle offline scenarios** gracefully
6. **Update UI immediately** based on API responses
7. **Show clear error messages** to users
8. **Respect remaining attempts** counter
9. **Periodically refresh attempts** to sync with server state

## 🔄 Usage Flow

```
1. Load rewards → GET /api/user/rewards
2. Check attempts → GET /api/user/rewards/remaining-attempts  
3. Show wheel with attempts counter
4. User clicks spin → GET /api/user/rewards/spin
5. Update attempts from response
6. Refresh attempts periodically or after other actions
```