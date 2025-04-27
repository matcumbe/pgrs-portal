// Admin Mock Data Module

/**
 * Returns mock data for development and testing
 * @param {string} endpoint - The API endpoint 
 * @returns {Object} Mock data for the requested endpoint
 */
export function getMockData(endpoint) {
    // Extract the main part of the endpoint
    const endpointPath = endpoint.split('?')[0];
    
    // Mock location data
    if (endpointPath === '/api/admin/regions') {
        return [
            { name: 'NCR' },
            { name: 'Region I - Ilocos Region' },
            { name: 'Region II - Cagayan Valley' },
            { name: 'Region III - Central Luzon' },
            { name: 'Region IV-A - CALABARZON' },
            { name: 'Region IV-B - MIMAROPA' },
            { name: 'Region V - Bicol Region' },
            { name: 'Region VI - Western Visayas' },
            { name: 'Region VII - Central Visayas' },
            { name: 'Region VIII - Eastern Visayas' },
            { name: 'Region IX - Zamboanga Peninsula' },
            { name: 'Region X - Northern Mindanao' },
            { name: 'Region XI - Davao Region' },
            { name: 'Region XII - SOCCSKSARGEN' },
            { name: 'Region XIII - Caraga' },
            { name: 'CAR - Cordillera Administrative Region' },
            { name: 'BARMM - Bangsamoro Autonomous Region in Muslim Mindanao' }
        ];
    }
    
    if (endpointPath === '/api/admin/provinces') {
        return [
            { name: 'Metro Manila' },
            { name: 'Cavite' },
            { name: 'Laguna' },
            { name: 'Batangas' },
            { name: 'Rizal' },
            { name: 'Quezon' }
        ];
    }
    
    if (endpointPath === '/api/admin/cities') {
        return [
            { name: 'Quezon City' },
            { name: 'Manila' },
            { name: 'Makati' },
            { name: 'Pasig' },
            { name: 'Taguig' },
            { name: 'Pasay' }
        ];
    }
    
    if (endpointPath === '/api/admin/barangays') {
        return [
            { name: 'Barangay 1' },
            { name: 'Barangay 2' },
            { name: 'Barangay 3' },
            { name: 'Barangay 4' },
            { name: 'Barangay 5' }
        ];
    }
    
    // Mock station data
    if (endpointPath.match(/\/api\/admin\/stations\/vertical/)) {
        return generateMockStations('vertical', 25);
    }
    
    if (endpointPath.match(/\/api\/admin\/stations\/horizontal/)) {
        return generateMockStations('horizontal', 25);
    }
    
    if (endpointPath.match(/\/api\/admin\/stations\/gravity/)) {
        return generateMockStations('gravity', 25);
    }
    
    // Mock single station data
    if (endpointPath.match(/\/api\/admin\/station\/[A-Za-z0-9]+/)) {
        const stationId = endpointPath.split('/').pop();
        return generateMockStation(stationId);
    }
    
    // Default empty response
    return { message: 'No mock data available for this endpoint' };
}

/**
 * Generates mock stations data
 * @param {string} type - Station type
 * @param {number} count - Number of stations to generate
 * @returns {Array} Array of mock stations
 */
function generateMockStations(type, count) {
    const stations = [];
    
    for (let i = 1; i <= count; i++) {
        const id = `${type.charAt(0).toUpperCase()}${String(i).padStart(5, '0')}`;
        stations.push({
            station_id: id,
            type: type,
            station_name: `${type.charAt(0).toUpperCase()}${type.slice(1)} Station ${i}`,
            station_code: `${type.substring(0, 3).toUpperCase()}-${i}`,
            latitude: 14.0 + (Math.random() * 2),
            longitude: 120.0 + (Math.random() * 2),
            region: 'Region IV-A - CALABARZON',
            province: 'Cavite',
            city: 'Tagaytay',
            barangay: `Barangay ${i % 5 + 1}`,
            ...(type === 'vertical' && { 
                elevation: 100 + (Math.random() * 1000),
                accuracy_class: ['1CM', '2CM', '3CM', '5CM', '10CM'][Math.floor(Math.random() * 5)]
            }),
            ...(type === 'horizontal' && { 
                ellipsoidal_height: 100 + (Math.random() * 1000)
            }),
            ...(type === 'gravity' && { 
                gravity_value: 978000 + (Math.random() * 1000)
            }),
            order: Math.floor(Math.random() * 4) + 1,
            date_established: '2023-01-01',
            date_last_updated: '2023-06-15'
        });
    }
    
    return stations;
}

/**
 * Generates a mock station by ID
 * @param {string} id - Station ID
 * @returns {Object} Mock station data
 */
function generateMockStation(id) {
    const stationType = id.charAt(0).toLowerCase() === 'v' ? 'vertical' : 
                       id.charAt(0).toLowerCase() === 'h' ? 'horizontal' : 'gravity';
    
    return {
        station_id: id,
        type: stationType,
        station_name: `${stationType.charAt(0).toUpperCase()}${stationType.slice(1)} Station ${id.slice(1)}`,
        station_code: `${stationType.substring(0, 3).toUpperCase()}-${id.slice(1)}`,
        latitude: 14.5958,
        longitude: 120.9772,
        region: 'NCR',
        province: 'Metro Manila',
        city: 'Manila',
        barangay: 'Intramuros',
        order: 2,
        mark_type: 'Concrete',
        mark_status: 'Good Condition',
        mark_construction: 'Monument',
        authority: 'NAMRIA',
        encoder: 'Admin User',
        date_established: '2022-03-15',
        date_last_updated: '2023-05-20',
        site_description: 'Located near the entrance of Intramuros, this station provides a reliable reference point for geodetic surveys in the historic district.',
        access_instructions: 'Access through the main gate of Intramuros. The station is marked with a brass disk embedded in a concrete monument.',
        island_group: 'Luzon',
        is_active: true,
        
        // Type-specific data
        ...(stationType === 'vertical' && {
            elevation: 12.345,
            bm_plus: 0.5,
            accuracy_class: '2CM',
            elevation_order: 2,
            vertical_datum: 'MSL',
            elevation_authority: 'NAMRIA'
        }),
        
        ...(stationType === 'horizontal' && {
            ellipsoidal_height: 15.678,
            horizontal_order: 2,
            horizontal_datum: 'PRS92',
            northing_value: 1489532.123,
            easting_value: 655321.456,
            utm_zone: '51N',
            itrf_ellipsoidal_height: 15.896,
            itrf_error: 0.002,
            itrf_height_error: 0.003
        }),
        
        ...(stationType === 'gravity' && {
            gravity_value: 978234.567,
            standard_deviation: 0.003,
            gravity_order: 1,
            date_measured: '2022-05-10',
            gravity_meter: 'Scintrex CG-5',
            gravity_datum: 'IGSN71'
        })
    };
} 